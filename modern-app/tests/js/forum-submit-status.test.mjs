import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/forum-submit-status.js', import.meta.url), 'utf8');

const setup = (type = 'topic') => {
    class Element {
        constructor() {
            this.attributes = new Map();
            this.dataset = {};
            this.listeners = new Map();
            this.classes = new Set();
            this.classList = {
                add: (name) => this.classes.add(name),
                remove: (name) => this.classes.delete(name),
            };
        }
        addEventListener(name, listener) { this.listeners.set(name, listener); }
        getAttribute(name) { return this.attributes.get(name) ?? null; }
        setAttribute(name, value) { this.attributes.set(name, value); }
        removeAttribute(name) { this.attributes.delete(name); }
        hasAttribute(name) { return this.attributes.has(name); }
    }

    class Form extends Element {
        constructor() {
            super();
            this.dataset.forumSubmit = type;
            this.setAttribute('data-forum-submit', type);
            this.buttons = [{ disabled: false }, { disabled: true }];
            this.body = { value: 'Post contents', disabled: false };
        }
        querySelectorAll() { return this.buttons; }
    }

    class Dialog extends Element {
        constructor() {
            super();
            this.open = false;
            this.dataset = { topicTitle: 'Posting your topic', replyTitle: 'Posting your reply' };
        }
        querySelector() { return title; }
        showModal() { this.open = true; }
        close() { this.open = false; }
    }

    const title = new Element();
    const dialog = new Dialog();
    const form = new Form();
    const document = new Element();
    const window = new Element();
    document.documentElement = new Element();
    document.querySelector = () => dialog;
    vm.runInNewContext(source, { document, window, HTMLDialogElement: Dialog, HTMLElement: Element, HTMLFormElement: Form });
    document.listeners.get('DOMContentLoaded')();

    const submit = (defaultPrevented = false) => {
        const event = { target: form, defaultPrevented, preventDefault() { this.defaultPrevented = true; } };
        document.listeners.get('submit')(event);
        return event;
    };

    return { dialog, form, document, title, window, submit };
};

test('valid topic submission opens a wait dialog without cancelling the request or disabling message fields', () => {
    const { dialog, form, title, submit } = setup();
    assert.equal(submit().defaultPrevented, false);
    assert.equal(dialog.open, true);
    assert.equal(title.textContent, 'Posting your topic');
    assert.equal(form.getAttribute('aria-busy'), 'true');
    assert.equal(form.buttons[0].disabled, true);
    assert.equal(form.body.disabled, false);
    assert.equal(form.body.value, 'Post contents');
});

test('reply submission uses the reply title', () => {
    const { title, submit } = setup('reply');
    submit();
    assert.equal(title.textContent, 'Posting your reply');
});

test('client validation and unfinished uploads can cancel without showing a wait dialog', () => {
    const { dialog, form, submit } = setup();
    submit(true);
    assert.equal(dialog.open, false);
    assert.equal(form.buttons[0].disabled, false);
    assert.equal(form.dataset.forumSubmitting, undefined);
});

test('duplicate submissions are cancelled while the first submission continues', () => {
    const { submit } = setup();
    assert.equal(submit().defaultPrevented, false);
    assert.equal(submit().defaultPrevented, true);
});

test('unrelated forms do not open the dialog', () => {
    const { dialog, form, submit } = setup();
    form.removeAttribute('data-forum-submit');
    assert.equal(submit().defaultPrevented, false);
    assert.equal(dialog.open, false);
});

test('Escape cannot dismiss the dialog during submission', () => {
    const { dialog, submit } = setup();
    submit();
    const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
    dialog.listeners.get('cancel')(event);
    assert.equal(event.defaultPrevented, true);
});

test('returning with Back restores the previous button and busy states', () => {
    const { dialog, form, document, window, submit } = setup();
    form.setAttribute('aria-busy', 'false');
    submit();
    window.listeners.get('pageshow')({ persisted: true });
    assert.equal(dialog.open, false);
    assert.equal(form.buttons[0].disabled, false);
    assert.equal(form.buttons[1].disabled, true);
    assert.equal(form.getAttribute('aria-busy'), 'false');
    assert.equal(form.dataset.forumSubmitting, undefined);
    assert.equal(document.documentElement.classes.has('forum-submit-open'), false);
    assert.equal(submit().defaultPrevented, false);
});

test('an initial pageshow does not clear a submission already in progress', () => {
    const { dialog, window, submit } = setup();
    submit();
    window.listeners.get('pageshow')({ persisted: false });
    assert.equal(dialog.open, true);
});
