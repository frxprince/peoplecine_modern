import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/forum-submit-status.js', import.meta.url), 'utf8');
const flush = () => new Promise(setImmediate);

const setup = (type = 'topic') => {
    class Element {
        constructor() {
            this.attributes = new Map();
            this.dataset = {};
            this.listeners = new Map();
            this.classes = new Set();
            this.children = [];
            this.textContent = '';
            this.classList = { add: (name) => this.classes.add(name), remove: (name) => this.classes.delete(name) };
        }
        addEventListener(name, listener) { this.listeners.set(name, listener); }
        getAttribute(name) { return this.attributes.get(name) ?? null; }
        setAttribute(name, value) { this.attributes.set(name, value); }
        removeAttribute(name) { this.attributes.delete(name); }
        hasAttribute(name) { return this.attributes.has(name); }
        append(child) { this.children.push(child); }
        prepend(child) { this.children.unshift(child); }
        focus() { this.focused = true; }
        remove() { this.removed = true; }
    }
    class Form extends Element {
        constructor() {
            super();
            this.dataset = { forumSubmit: type, forumRecoveryUrl: '/rooms/test-room' };
            this.setAttribute('data-forum-submit', type);
            this.action = 'https://www.peoplecine.com/topics/99/replies';
            this.method = 'post';
            this.buttons = [{ disabled: false }, { disabled: true }];
            this.body = { value: 'Post contents', disabled: false };
        }
        querySelectorAll(selector) {
            return selector.includes('button[') ? this.buttons : this.children.filter((child) => child.dataset.forumSubmitError && !child.removed);
        }
    }
    class FormData {
        constructor(form) {
            this.values = new Map([['_token', 'csrf-token'], ['body_html', form.body.value]]);
            if (form.dataset.forumSubmit === 'delete-topic') this.values.set('_method', 'DELETE');
        }
        append(name, value) { this.values.set(name, value); }
        get(name) { return this.values.get(name); }
    }

    const title = new Element();
    const description = new Element();
    description.textContent = 'Please wait while saving.';
    const spinner = new Element();
    const actions = new Element();
    const recover = new Element();
    const close = new Element();
    class Dialog extends Element {
        constructor() {
            super();
            this.open = false;
            this.dataset = {
                topicTitle: 'Posting your topic', replyTitle: 'Posting your reply', deleteTopicTitle: 'Deleting the topic',
                deleteDescription: 'Returning to the room after deletion.', errorTitle: 'Unable to confirm completion',
                uncertainMessage: 'Check before retrying.', sessionMessage: 'Session expired.',
                permissionMessage: 'Permission denied.', validationMessage: 'Check the form.',
            };
        }
        querySelector(selector) {
            return new Map([
                ['[data-forum-submit-title]', title], ['[data-forum-submit-description]', description],
                ['[data-forum-submit-spinner]', spinner], ['[data-forum-submit-actions]', actions],
                ['[data-forum-submit-recover]', recover], ['[data-forum-submit-close]', close],
            ]).get(selector);
        }
        showModal() { this.open = true; }
        close() { this.open = false; this.listeners.get('close')?.(); }
    }

    const requests = [];
    const navigations = [];
    const timers = new Map();
    let resolveResponse;
    let rejectResponse;
    const fetch = (url, options) => {
        requests.push({ url, options });
        return new Promise((resolve, reject) => {
            resolveResponse = resolve;
            rejectResponse = reject;
            options.signal.addEventListener('abort', () => reject(new Error('Timed out')));
        });
    };
    const dialog = new Dialog();
    const form = new Form();
    const document = new Element();
    const window = new Element();
    document.documentElement = new Element();
    document.querySelector = () => dialog;
    document.createElement = () => new Element();
    window.location = { origin: 'https://www.peoplecine.com', assign: (path) => navigations.push(path) };
    window.setTimeout = (callback, delay) => { timers.set(1, { callback, delay }); return 1; };
    window.clearTimeout = (id) => timers.delete(id);
    vm.runInNewContext(source, { document, window, HTMLDialogElement: Dialog, HTMLFormElement: Form, FormData, fetch, AbortController, URL });
    document.listeners.get('DOMContentLoaded')();

    const submit = (defaultPrevented = false) => {
        const event = { target: form, defaultPrevented, preventDefault() { this.defaultPrevented = true; } };
        document.listeners.get('submit')(event);
        return event;
    };
    const respond = async (status, payload) => {
        resolveResponse({ ok: status >= 200 && status < 300, status, json: async () => payload });
        await flush();
    };
    const reject = async () => { rejectResponse(new Error('Network failed')); await flush(); };
    return { dialog, form, document, window, title, description, spinner, actions, recover, close, requests, navigations, timers, submit, respond, reject };
};

test('valid submission sends one CSRF-protected request and leaves message fields intact', () => {
    const state = setup();
    assert.equal(state.submit().defaultPrevented, true);
    assert.equal(state.dialog.open, true);
    assert.equal(state.title.textContent, 'Posting your topic');
    assert.equal(state.form.buttons[0].disabled, true);
    assert.equal(state.form.body.disabled, false);
    assert.equal(state.requests[0].options.body.get('_token'), 'csrf-token');
    assert.equal(state.requests[0].options.body.get('body_html'), 'Post contents');
    assert.equal(state.requests[0].options.headers.Accept, 'application/json');
    assert.equal(state.requests[0].options.redirect, 'error');
    state.submit();
    assert.equal(state.requests.length, 1);
});

test('confirmed posting closes the wait dialog and navigates to the topic', async () => {
    const state = setup();
    state.submit();
    await state.respond(201, { redirect: '/topics/99' });
    assert.equal(state.dialog.open, false);
    assert.deepEqual(state.navigations, ['/topics/99']);
    assert.equal(state.timers.size, 0);
});

test('reply uses the reply status and navigates after success', async () => {
    const state = setup('reply');
    state.submit();
    assert.equal(state.title.textContent, 'Posting your reply');
    await state.respond(201, { redirect: '/topics/99' });
    assert.equal(state.dialog.open, false);
    assert.deepEqual(state.navigations, ['/topics/99']);
});

test('confirmed deletion preserves DELETE method spoofing and navigates to the room lobby', async () => {
    const state = setup('delete-topic');
    state.submit();
    assert.equal(state.title.textContent, 'Deleting the topic');
    assert.equal(state.requests[0].options.body.get('_method'), 'DELETE');
    await state.respond(200, { redirect: '/rooms/test-room' });
    assert.equal(state.dialog.open, false);
    assert.deepEqual(state.navigations, ['/rooms/test-room']);
});

test('cancelled confirmation, invalid fields and pending uploads send no request', () => {
    const state = setup();
    state.submit(true);
    assert.equal(state.dialog.open, false);
    assert.equal(state.form.buttons[0].disabled, false);
    assert.equal(state.requests.length, 0);
});

test('validation errors close the dialog, retain content and allow correction', async () => {
    const state = setup();
    state.submit();
    await state.respond(422, { errors: { title: ['Title is required.'] } });
    assert.equal(state.dialog.open, false);
    assert.equal(state.form.buttons[0].disabled, false);
    assert.equal(state.form.body.value, 'Post contents');
    assert.equal(state.form.children[0].children[0].textContent, 'Title is required.');
    assert.equal(state.navigations.length, 0);
});

test('expired sessions stop waiting and display an actionable error', async () => {
    const state = setup();
    state.submit();
    await state.respond(419, {});
    assert.equal(state.dialog.open, false);
    assert.equal(state.form.children[0].children[0].textContent, 'Session expired.');
});

test('server failures show recovery instead of an infinite spinner or blind retry', async () => {
    const state = setup();
    state.submit();
    await state.respond(500, {});
    assert.equal(state.spinner.hidden, true);
    assert.equal(state.actions.hidden, false);
    assert.equal(state.form.buttons[0].disabled, true);
    state.submit();
    assert.equal(state.requests.length, 1);
    state.recover.listeners.get('click')();
    assert.equal(state.dialog.open, false);
    assert.deepEqual(state.navigations, ['/rooms/test-room']);
});

test('a network failure offers recovery without automatically resubmitting', async () => {
    const state = setup();
    state.submit();
    await state.reject();
    assert.equal(state.spinner.hidden, true);
    assert.equal(state.title.textContent, 'Unable to confirm completion');
    assert.equal(state.requests.length, 1);
});

test('a request timeout stops the spinner and exposes recovery controls', async () => {
    const state = setup();
    state.submit();
    const timer = state.timers.get(1);
    assert.equal(timer.delay, 120000);
    timer.callback();
    await flush();
    assert.equal(state.requests[0].options.signal.aborted, true);
    assert.equal(state.spinner.hidden, true);
    assert.equal(state.actions.hidden, false);
});

test('a success response without a destination does not leave the spinner running', async () => {
    const state = setup();
    state.submit();
    await state.respond(200, {});
    assert.equal(state.spinner.hidden, true);
    assert.equal(state.navigations.length, 0);
});

test('off-origin success destinations are rejected', async () => {
    for (const redirect of ['https://other.example', '//other.example', '/\\other.example']) {
        const state = setup();
        state.submit();
        await state.respond(201, { redirect });
        assert.equal(state.navigations.length, 0);
        assert.equal(state.spinner.hidden, true);
    }
});

test('Escape is blocked only while the request is pending', async () => {
    const state = setup();
    state.submit();
    const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
    state.dialog.listeners.get('cancel')(event);
    assert.equal(event.defaultPrevented, true);
    await state.reject();
    event.defaultPrevented = false;
    state.dialog.listeners.get('cancel')(event);
    assert.equal(event.defaultPrevented, false);
});

test('returning with Back restores the previous button and busy states', async () => {
    const state = setup();
    state.form.setAttribute('aria-busy', 'false');
    state.submit();
    await state.respond(201, { redirect: '/topics/99' });
    state.window.listeners.get('pageshow')({ persisted: true });
    assert.equal(state.form.buttons[0].disabled, false);
    assert.equal(state.form.buttons[1].disabled, true);
    assert.equal(state.form.getAttribute('aria-busy'), 'false');
    assert.equal(state.form.dataset.forumSubmitting, undefined);
});

test('unrelated forms are untouched', () => {
    const state = setup();
    state.form.removeAttribute('data-forum-submit');
    assert.equal(state.submit().defaultPrevented, false);
    assert.equal(state.requests.length, 0);
});

test('a late response cannot reopen the dialog or navigate after the page was restored', async () => {
    const state = setup();
    state.submit();
    state.window.listeners.get('pageshow')({ persisted: true });
    await state.respond(201, { redirect: '/topics/99' });
    assert.equal(state.dialog.open, false);
    assert.equal(state.form.buttons[0].disabled, false);
    assert.equal(state.navigations.length, 0);
});
