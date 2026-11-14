document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-forum-submit-dialog]');
    const title = dialog?.querySelector('[data-forum-submit-title]');

    if (!(dialog instanceof HTMLDialogElement) || !(title instanceof HTMLElement)) {
        return;
    }

    let activeForm = null;
    let previousBusy = null;
    const buttonStates = new Map();

    const reset = () => {
        if (activeForm) {
            if (previousBusy === null) {
                activeForm.removeAttribute('aria-busy');
            } else {
                activeForm.setAttribute('aria-busy', previousBusy);
            }

            delete activeForm.dataset.forumSubmitting;
        }

        buttonStates.forEach((disabled, button) => { button.disabled = disabled; });
        buttonStates.clear();
        activeForm = null;

        if (dialog.open) {
            dialog.close();
        }

        document.documentElement.classList.remove('forum-submit-open');
    };

    dialog.addEventListener('cancel', (event) => {
        if (activeForm) {
            event.preventDefault();
        }
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            reset();
        }
    });

    // Bubble after the form's validation and upload listeners have had a chance to cancel.
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-forum-submit')) {
            return;
        }

        if (activeForm) {
            event.preventDefault();
            return;
        }

        if (event.defaultPrevented) {
            return;
        }

        previousBusy = form.getAttribute('aria-busy');
        activeForm = form;
        form.dataset.forumSubmitting = 'true';
        form.setAttribute('aria-busy', 'true');

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            buttonStates.set(button, button.disabled);
            button.disabled = true;
        });

        title.textContent = form.dataset.forumSubmit === 'reply'
            ? dialog.dataset.replyTitle
            : dialog.dataset.topicTitle;

        document.documentElement.classList.add('forum-submit-open');
        dialog.showModal();
    });
});
