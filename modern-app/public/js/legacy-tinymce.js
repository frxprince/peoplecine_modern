document.addEventListener('DOMContentLoaded', () => {
    if (!window.tinymce) {
        return;
    }

    const extractClipboardImages = (event) => {
        const clipboardData = event?.clipboardData
            ?? event?.originalEvent?.clipboardData
            ?? event?.raw?.clipboardData
            ?? null;

        if (!clipboardData) {
            return [];
        }

        if (clipboardData.files && clipboardData.files.length > 0) {
            return Array.from(clipboardData.files).filter((file) => file.type.startsWith('image/'));
        }

        if (!clipboardData.items || clipboardData.items.length === 0) {
            return [];
        }

        return Array.from(clipboardData.items)
            .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
            .map((item) => item.getAsFile())
            .filter((file) => file instanceof File);
    };

    const dataUrlToFile = (dataUrl) => {
        if (typeof dataUrl !== 'string' || !dataUrl.startsWith('data:image/')) {
            return null;
        }

        const match = dataUrl.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/);
        if (!match) {
            return null;
        }

        const mimeType = match[1];
        const base64Data = match[2];

        try {
            const binary = window.atob(base64Data);
            const bytes = new Uint8Array(binary.length);

            for (let index = 0; index < binary.length; index += 1) {
                bytes[index] = binary.charCodeAt(index);
            }

            const extension = mimeType.split('/')[1] || 'png';
            const fileName = `pasted-image-${Date.now()}-${Math.random().toString(36).slice(2, 8)}.${extension}`;

            return new File([bytes], fileName, {
                type: mimeType,
                lastModified: Date.now(),
            });
        } catch (error) {
            return null;
        }
    };

    const queueImagesIntoUploader = (form, files) => {
        if (!form || !Array.isArray(files) || files.length === 0) {
            return false;
        }

        const uploader = form.querySelector('[data-staged-uploader]');
        if (!(uploader instanceof HTMLElement)) {
            return false;
        }

        const uploaderAddFiles = uploader.peoplecineAddStagedFiles;
        if (typeof uploaderAddFiles === 'function') {
            uploaderAddFiles(files);
            return true;
        }

        form.dispatchEvent(new CustomEvent('peoplecine:add-staged-files', {
            detail: { files },
        }));

        return true;
    };

    const editors = Array.from(document.querySelectorAll('[data-tinymce-textarea]'))
        .filter((element) => element instanceof HTMLTextAreaElement);

    if (editors.length === 0) {
        return;
    }

    const baseUrl = window.peoplecineTinyMceBase ?? '/vendor/tinymce';
    const initializedForms = new WeakSet();

    editors.forEach((textarea) => {
        const form = textarea.closest('form');
        const minHeight = Number.parseInt(textarea.dataset.editorHeight ?? '320', 10) || 320;
        const label = textarea.dataset.editorLabel ?? 'Message editor';
        const placeholder = textarea.dataset.editorPlaceholder ?? '';

        window.tinymce.init({
            target: textarea,
            base_url: baseUrl,
            suffix: '.min',
            license_key: 'gpl',
            menubar: 'edit view insert format tools table',
            plugins: 'autolink autoresize charmap code emoticons fullscreen hr link lists preview searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote hr | link table charmap emoticons | removeformat code fullscreen preview',
            toolbar_mode: 'sliding',
            min_height: minHeight,
            autoresize_bottom_margin: 18,
            branding: false,
            promotion: false,
            resize: true,
            browser_spellcheck: true,
            relative_urls: false,
            convert_urls: false,
            link_default_target: '_blank',
            link_assume_external_targets: 'https',
            placeholder,
            content_style: "body { font-family: Tahoma, 'MS Sans Serif', 'Segoe UI', sans-serif; font-size: 14px; line-height: 1.7; }",
            setup: (editor) => {
                editor.on('init change input undo redo', () => {
                    editor.save();
                });

                editor.on('init', () => {
                    const container = editor.getContainer();
                    container?.setAttribute('aria-label', label);
                });

                editor.on('paste', (event) => {
                    const pastedImages = extractClipboardImages(event);

                    if (pastedImages.length === 0 || !form) {
                        return;
                    }

                    if (!queueImagesIntoUploader(form, pastedImages)) {
                        return;
                    }

                    event.preventDefault();
                });

                editor.on('PastePostProcess', (event) => {
                    if (!form || !(event?.node instanceof Element)) {
                        return;
                    }

                    const dataImageNodes = Array.from(
                        event.node.querySelectorAll('img[src^="data:image/"]'),
                    );

                    if (dataImageNodes.length === 0) {
                        return;
                    }

                    const files = dataImageNodes
                        .map((imageNode) => dataUrlToFile(imageNode.getAttribute('src') ?? ''))
                        .filter((file) => file instanceof File);

                    if (files.length === 0) {
                        return;
                    }

                    if (!queueImagesIntoUploader(form, files)) {
                        return;
                    }

                    dataImageNodes.forEach((imageNode) => imageNode.remove());
                });
            },
        });

        if (form && !initializedForms.has(form)) {
            form.addEventListener('submit', () => {
                window.tinymce?.triggerSave();
            });
            initializedForms.add(form);
        }
    });
});
