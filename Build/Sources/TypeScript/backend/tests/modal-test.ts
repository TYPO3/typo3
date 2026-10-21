/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { html } from 'lit';
import { expect } from '@open-wc/testing';
import Modal, { type ModalElement } from '@typo3/backend/modal.js';
import type { } from 'mocha';

describe('@typo3/backend/modal', () => {
  let modal: ModalElement;
  const nativeRequestAnimationFrame = window.requestAnimationFrame;

  const shown = (element: ModalElement): Promise<void> => new Promise(resolve => {
    element.addEventListener('typo3-modal-shown', () => resolve(), { once: true });
  });

  before(() => {
    // The test page runs in the background, animation frames never fire there
    window.requestAnimationFrame = (callback: FrameRequestCallback): number => window.setTimeout(() => callback(performance.now()), 0);
  });

  after(() => {
    window.requestAnimationFrame = nativeRequestAnimationFrame;
  });

  afterEach(() => {
    // The dialog close event does not fire in the background either
    modal?.hideModal();
    modal?.remove();
  });

  it('binds a footer button to the form given by its configuration', async () => {
    modal = Modal.advanced({
      content: html`<form id="modal-test-form"><input name="username"><input name="password"></form>`,
      buttons: [{ text: 'ok', btnClass: 'btn-primary', form: 'modal-test-form' }],
    });
    await shown(modal);

    const button = modal.querySelector<HTMLButtonElement>('.modal-footer button');
    expect(button.type).to.equal('submit');
    expect(button.form?.id).to.equal('modal-test-form');
  });

  it('keeps the focus on an autofocus field instead of the active button', async () => {
    modal = Modal.advanced({
      content: html`<form><input name="password" autofocus></form>`,
      buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }],
    });
    await shown(modal);

    expect(document.activeElement.matches('input[name="password"]')).to.be.true;
  });

  it('focuses an autofocus field of ajax content loaded after the modal was shown', async () => {
    const nativeFetch = window.fetch;
    window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
      await shown(modal);
      return nativeFetch(input, init);
    };
    modal = Modal.advanced({
      type: Modal.types.ajax,
      content: '/Build/Sources/TypeScript/backend/tests/fixtures/modal-autofocus.html',
      buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }],
    });
    const loaded = new Promise<void>(resolve => modal.addEventListener('modal-loaded', () => resolve(), { once: true }));
    await loaded;
    window.fetch = nativeFetch;

    expect(document.activeElement.matches('input[name="filter"]')).to.be.true;
  });

  describe('returning the focus after closing', () => {
    let iframe: HTMLIFrameElement;

    const close = (element: ModalElement): void => {
      element.hideModal();
      // The dialog close event does not fire in the background, so it is dispatched here
      element.querySelector('dialog').dispatchEvent(new Event('close'));
    };

    afterEach(() => {
      iframe?.remove();
    });

    it('focuses the element inside a frame that opened the modal', async () => {
      iframe = document.createElement('iframe');
      document.body.appendChild(iframe);
      const trigger = iframe.contentDocument.createElement('button');
      iframe.contentDocument.body.appendChild(trigger);
      trigger.focus();

      modal = Modal.advanced({ content: 'content', buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }] });
      await shown(modal);
      close(modal);

      // Elements are compared as booleans: printing a mismatched element hangs the test runner
      expect(document.activeElement === iframe).to.be.true;
      expect(iframe.contentDocument.activeElement === trigger).to.be.true;
    });

    it('focuses the element inside a frame of an open modal that opened another modal', async () => {
      const outer = Modal.advanced({ content: html`<iframe id="modal-test-frame"></iframe>`, buttons: [] });
      await shown(outer);
      const frame = outer.querySelector<HTMLIFrameElement>('#modal-test-frame');
      const trigger = frame.contentDocument.createElement('button');
      frame.contentDocument.body.appendChild(trigger);
      trigger.focus();

      modal = Modal.advanced({ content: 'info', buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }] });
      await shown(modal);
      close(modal);

      const focusIsBack = document.activeElement === frame && frame.contentDocument.activeElement === trigger;
      outer.hideModal();
      outer.remove();
      expect(focusIsBack).to.be.true;
    });

    it('focuses the element that opened the modal', async () => {
      const trigger = document.createElement('button');
      document.body.appendChild(trigger);
      trigger.focus();

      modal = Modal.advanced({ content: 'content', buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }] });
      await shown(modal);
      close(modal);

      expect(document.activeElement === trigger).to.be.true;
      trigger.remove();
    });

    it('leaves the focus alone when the element that opened the modal is gone', async () => {
      const trigger = document.createElement('button');
      document.body.appendChild(trigger);
      trigger.focus();

      modal = Modal.advanced({ content: 'content', buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }] });
      await shown(modal);
      trigger.remove();

      expect(() => close(modal)).not.to.throw();
    });
  });

  it('focuses the active button when the body has no autofocus field', async () => {
    modal = Modal.advanced({
      content: html`<form><input name="password"></form>`,
      buttons: [{ text: 'ok', btnClass: 'btn-primary', active: true }],
    });
    await shown(modal);

    expect(document.activeElement.matches('.modal-footer button')).to.be.true;
  });
});
