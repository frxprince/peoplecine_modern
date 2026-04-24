document.addEventListener('DOMContentLoaded', () => {
    const parseRichText = (html) => {
        if (typeof html !== 'string' || html.trim() === '') {
            return '';
        }

        const parser = new DOMParser();
        const parsed = parser.parseFromString(`<body>${html}</body>`, 'text/html');

        parsed.querySelectorAll('br').forEach((lineBreak) => {
            lineBreak.replaceWith('\n');
        });

        return (parsed.body.textContent ?? '')
            .replace(/\u00a0/g, ' ')
            .trim();
    };

    const clearClientErrors = (form) => {
        form.querySelectorAll('.form-error--client').forEach((node) => node.remove());
        form.querySelectorAll('.form-field--invalid').forEach((node) => node.classList.remove('form-field--invalid'));
        form.querySelectorAll('.legacy-uploader--invalid').forEach((node) => node.classList.remove('legacy-uploader--invalid'));
    };

    const appendError = (container, message) => {
        if (!(container instanceof HTMLElement) || message.trim() === '') {
            return;
        }

        const errorNode = document.createElement('p');
        errorNode.className = 'form-error form-error--client';
        errorNode.textContent = message;
        container.append(errorNode);
    };

    const showFieldError = (field, message) => {
        const container = field?.closest('[data-forum-field]') ?? field?.closest('.form-field') ?? null;

        if (!(container instanceof HTMLElement)) {
            return;
        }

        container.classList.add('form-field--invalid');
        appendError(container, message);
    };

    const showUploaderError = (form, message) => {
        const uploader = form.querySelector('[data-staged-uploader]');
        const container = uploader?.closest('.form-field') ?? null;

        if (uploader instanceof HTMLElement) {
            uploader.classList.add('legacy-uploader--invalid');
        }

        if (container instanceof HTMLElement) {
            appendError(container, message);
        }
    };

    const countRemainingManagedImages = (form) => {
        const toggles = Array.from(form.querySelectorAll('input[name="remove_attachment_ids[]"]'))
            .filter((input) => input instanceof HTMLInputElement);

        if (toggles.length === 0) {
            return 0;
        }

        return toggles.filter((input) => !input.checked).length;
    };

    const countUploadedImages = (form) => form.querySelectorAll('[data-staged-hidden-inputs] input').length;

    const focusField = (field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }

        if (field instanceof HTMLTextAreaElement && field.id !== '') {
            const editor = window.tinymce?.get(field.id);

            if (editor) {
                editor.focus();
                return;
            }
        }

        field.focus();
    };

    document.querySelectorAll('[data-forum-validate]').forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.addEventListener('submit', (event) => {
            window.tinymce?.triggerSave();
            clearClientErrors(form);

            const titleInput = form.querySelector('[data-forum-required="title"]');
            const bodyInput = form.querySelector('[data-forum-required="body"], textarea[name="body_html"], textarea[name="message_body"]');
            const requiresTitle = form.dataset.requireTitle === 'true';
            const requiresBody = form.dataset.requireBody === 'true';
            const requiresBodyOrImage = form.dataset.requireBodyOrImage === 'true';
            const titleMessage = form.dataset.errorTitleRequired ?? 'Please enter a title before posting.';
            const bodyMessage = form.dataset.errorBodyRequired ?? 'Please enter a message before posting.';
            const bodyOrImageMessage = form.dataset.errorBodyOrImageRequired ?? 'Please enter a message or upload at least one image before posting.';

            let firstInvalidField = null;
            let hasErrors = false;

            if (requiresTitle && titleInput instanceof HTMLInputElement && titleInput.value.trim() === '') {
                hasErrors = true;
                firstInvalidField ??= titleInput;
                showFieldError(titleInput, titleMessage);
            }

            const parsedBody = bodyInput instanceof HTMLTextAreaElement ? parseRichText(bodyInput.value) : '';
            const totalImages = countUploadedImages(form) + countRemainingManagedImages(form);

            if (requiresBody && bodyInput instanceof HTMLTextAreaElement && parsedBody === '') {
                hasErrors = true;
                firstInvalidField ??= bodyInput;
                showFieldError(bodyInput, bodyMessage);
            }

            if (requiresBodyOrImage && bodyInput instanceof HTMLTextAreaElement && parsedBody === '' && totalImages === 0) {
                hasErrors = true;
                firstInvalidField ??= bodyInput;
                showFieldError(bodyInput, bodyOrImageMessage);
                showUploaderError(form, bodyOrImageMessage);
            }

            if (!hasErrors) {
                return;
            }

            event.preventDefault();
            focusField(firstInvalidField);
        });
    });
});
