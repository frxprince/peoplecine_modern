document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-forum-submit-dialog]');
    const title = dialog?.querySelector('[data-forum-submit-title]');
    const description = dialog?.querySelector('[data-forum-submit-description]');
    const spinner = dialog?.querySelector('[data-forum-submit-spinner]');
    const actions = dialog?.querySelector('[data-forum-submit-actions]');
    const recoverButton = dialog?.querySelector('[data-forum-submit-recover]');
    const closeButton = dialog?.querySelector('[data-forum-submit-close]');

    if (!(dialog instanceof HTMLDialogElement) || !title || !description || !spinner || !actions || !recoverButton || !closeButton) {
        return;
    }

    const originalDescription = description.textContent;
    let activeForm = null;
    let previousBusy = null;
    let pending = false;
    let requestGeneration = 0;
    const buttonStates = new Map();

    const closeDialog = () => {
        if (dialog.open) {
            dialog.close();
        }
        document.documentElement.classList.remove('forum-submit-open');
    };

    const reset = () => {
        requestGeneration++;
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
        pending = false;
        closeDialog();
    };

    const localPath = (value) => {
        if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) {
            throw new Error('Invalid navigation target.');
        }
        const target = new URL(value, window.location.origin);
        if (target.origin !== window.location.origin) {
            throw new Error('Cross-origin navigation is not allowed.');
        }
        return target.pathname + target.search + target.hash;
    };

    const showErrors = (form, messages) => {
        const error = document.createElement('div');
        error.className = 'form-error form-error--submission';
        error.dataset.forumSubmitError = 'true';
        error.setAttribute('role', 'alert');
        error.setAttribute('tabindex', '-1');
        messages.forEach((message) => {
            const line = document.createElement('p');
            line.textContent = String(message);
            error.append(line);
        });
        form.prepend(error);
        error.focus();
    };

    const showUncertainResult = () => {
        pending = false;
        spinner.hidden = true;
        actions.hidden = false;
        title.textContent = dialog.dataset.errorTitle;
        description.textContent = dialog.dataset.uncertainMessage;
        // A lost response does not mean saving failed; do not enable a blind retry.
        if (activeForm) {
            activeForm.setAttribute('aria-busy', 'false');
        }
        if (!dialog.open) {
            dialog.showModal();
        }
        document.documentElement.classList.add('forum-submit-open');
        recoverButton.focus();
    };

    const send = async (form, body) => {
        const generation = ++requestGeneration;
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 120000);

        try {
            const response = await fetch(form.action, {
                method: form.method.toUpperCase(),
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                redirect: 'error',
                signal: controller.signal,
            });
            const payload = await response.json().catch(() => null);

            if (generation !== requestGeneration) {
                return;
            }

            if (response.ok && payload && typeof payload.redirect === 'string') {
                const destination = localPath(payload.redirect);
                pending = false;
                closeDialog();
                window.location.assign(destination);
                return;
            }

            if ([401, 403, 419, 422].includes(response.status)) {
                const messages = response.status === 422
                    ? Object.values(payload?.errors ?? {}).flat()
                    : [response.status === 403 ? dialog.dataset.permissionMessage : dialog.dataset.sessionMessage];
                reset();
                showErrors(form, messages.length ? messages : [dialog.dataset.validationMessage]);
                return;
            }

            showUncertainResult();
        } catch {
            if (generation === requestGeneration) {
                showUncertainResult();
            }
        } finally {
            window.clearTimeout(timer);
        }
    };

    dialog.addEventListener('cancel', (event) => {
        if (pending) {
            event.preventDefault();
        }
    });
    dialog.addEventListener('close', () => document.documentElement.classList.remove('forum-submit-open'));
    closeButton.addEventListener('click', closeDialog);
    recoverButton.addEventListener('click', () => {
        const destination = localPath(activeForm?.dataset.forumRecoveryUrl ?? '/forum');
        closeDialog();
        window.location.assign(destination);
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            reset();
        }
    });

    // Bubble after the form's validation, confirmation and upload listeners.
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

        event.preventDefault();
        const body = new FormData(form);
        if (event.submitter?.name) {
            body.append(event.submitter.name, event.submitter.value);
        }
        form.querySelectorAll('[data-forum-submit-error]').forEach((error) => error.remove());
        previousBusy = form.getAttribute('aria-busy');
        activeForm = form;
        pending = true;
        form.dataset.forumSubmitting = 'true';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            buttonStates.set(button, button.disabled);
            button.disabled = true;
        });

        const deleting = form.dataset.forumSubmit === 'delete-topic';
        title.textContent = deleting ? dialog.dataset.deleteTopicTitle
            : (form.dataset.forumSubmit === 'reply' ? dialog.dataset.replyTitle : dialog.dataset.topicTitle);
        description.textContent = deleting ? dialog.dataset.deleteDescription : originalDescription;
        spinner.hidden = false;
        actions.hidden = true;
        document.documentElement.classList.add('forum-submit-open');
        dialog.showModal();
        void send(form, body);
    });
});
