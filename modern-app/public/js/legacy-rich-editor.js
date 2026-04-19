document.addEventListener('DOMContentLoaded', () => {
    const isBlank = (html) => html
        .replace(/<br\s*\/?>/gi, '')
        .replace(/&nbsp;/gi, '')
        .replace(/\s+/g, '')
        .trim() === '';

    const normalizeHtml = (html) => {
        const normalized = (html ?? '').trim();

        return isBlank(normalized) ? '' : normalized;
    };

    const insertLink = (surface) => {
        const selection = window.getSelection();
        const selectedText = selection ? selection.toString().trim() : '';
        const initialValue = selectedText.startsWith('http://') || selectedText.startsWith('https://')
            ? selectedText
            : 'https://';
        const url = window.prompt('Enter the link URL', initialValue);

        if (!url) {
            return;
        }

        const trimmedUrl = url.trim();

        if (trimmedUrl === '') {
            return;
        }

        surface.focus();
        document.execCommand('createLink', false, trimmedUrl);
    };

    document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
        const textarea = editor.querySelector('[data-rich-editor-textarea]');
        const surface = editor.querySelector('[data-rich-editor-surface]');
        const toolbar = editor.querySelector('[data-rich-editor-toolbar]');
        const form = editor.closest('form');

        if (!(textarea instanceof HTMLTextAreaElement) || !(surface instanceof HTMLElement) || !(toolbar instanceof HTMLElement)) {
            return;
        }

        const syncToTextarea = () => {
            textarea.value = normalizeHtml(surface.innerHTML);
        };

        const updatePlaceholder = () => {
            surface.classList.toggle('rich-editor__surface--empty', normalizeHtml(surface.innerHTML) === '');
        };

        surface.innerHTML = textarea.value;
        surface.hidden = false;
        toolbar.hidden = false;
        textarea.classList.add('rich-editor__textarea--enhanced');
        updatePlaceholder();

        toolbar.querySelectorAll('button').forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            button.addEventListener('click', () => {
                const action = button.dataset.editorAction ?? '';
                const command = button.dataset.editorCommand ?? '';

                surface.focus();

                if (action === 'link') {
                    insertLink(surface);
                } else if (command !== '') {
                    document.execCommand(command, false, null);
                }

                syncToTextarea();
                updatePlaceholder();
            });
        });

        surface.addEventListener('input', () => {
            syncToTextarea();
            updatePlaceholder();
        });

        surface.addEventListener('blur', () => {
            syncToTextarea();
            updatePlaceholder();
        });

        surface.addEventListener('paste', (event) => {
            event.preventDefault();

            const text = event.clipboardData?.getData('text/plain') ?? '';
            if (text !== '') {
                document.execCommand('insertText', false, text);
            }
        });

        form?.addEventListener('submit', () => {
            syncToTextarea();
        });
    });
});
