document.addEventListener('DOMContentLoaded', () => {
    if (!window.tinymce) {
        return;
    }

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
