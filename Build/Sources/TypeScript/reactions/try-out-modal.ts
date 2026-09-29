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

import { customElement, property } from 'lit/decorators.js';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';
import Modal, { type ModalElement } from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import type { CodeMirrorElement } from '@typo3/backend/code-editor/element/code-mirror-element';

/**
 * Module: @typo3/reactions/try-out-modal
 *
 * A button opening the try out of a reaction in a modal, which sends
 * the dry run form it may carry without leaving the modal.
 *
 * @example
 * <typo3-reactions-try-out url="/typo3/ajax/reactions/try-out?…" modal-title="…" close-label="Close">…</typo3-reactions-try-out>
 *
 * The dry run form in the loaded content:
 * <form data-reactions-dry-run="/typo3/ajax/reactions/dry-run?…" data-reactions-dry-run-error="…">
 *   <textarea name="payload">…</textarea>
 *   <button type="button" data-reactions-dry-run-submit>…</button>
 *   <div data-reactions-dry-run-result></div>
 * </form>
 */
@customElement('typo3-reactions-try-out')
export class TryOutElement extends PseudoButtonLitElement {
  private static readonly editorTimeout = 10000;

  @property({ type: String }) url: string;
  @property({ type: String, attribute: 'modal-title' }) modalTitle: string = '';
  @property({ type: String, attribute: 'close-label' }) closeLabel: string = '';

  protected override buttonActivated(): void {
    // The payload is edited in a code editor and the example is copied with a button, whose
    // elements have to be defined in the document the modal is rendered into.
    const editorLoaded = topLevelModuleImport('@typo3/backend/code-editor/element/code-mirror-element.js');
    topLevelModuleImport('@typo3/backend/copy-to-clipboard.js').catch((error: unknown): void => {
      console.warn('The copy buttons could not be loaded.', error);
    });
    const modal = Modal.advanced({
      type: Modal.types.ajax,
      content: this.url,
      title: this.modalTitle,
      severity: SeverityEnum.notice,
      size: Modal.sizes.large,
      buttons: [
        {
          text: this.closeLabel,
          active: true,
          btnClass: 'btn-default',
          name: 'close',
          trigger: (): void => modal.hideModal(),
        },
      ],
      ajaxCallback: (loaded: ModalElement): void => this.bindDryRun(loaded, editorLoaded),
    });
  }

  private bindDryRun(modal: ModalElement, editorLoaded: Promise<unknown>): void {
    const form = modal.querySelector<HTMLFormElement>('form[data-reactions-dry-run]');
    if (form === null) {
      return;
    }
    new RegularEvent('submit', (event: SubmitEvent): void => {
      event.preventDefault();
    }).bindTo(form);
    new RegularEvent('click', (event: Event, button: HTMLButtonElement): void => {
      event.preventDefault();
      this.sendDryRun(form, button);
    }).delegateTo(form, '[data-reactions-dry-run-submit]');

    // The form is rendered with its button disabled: until the editor has taken over the
    // payload, its textarea is hidden in the editor and what would be sent is not what is
    // shown. Should the editor not load at all, its element is never upgraded and the
    // textarea stays visible, so the button is enabled either way.
    const editor = form.querySelector<CodeMirrorElement>('typo3-t3editor-codemirror');
    this.whenEditorIsReady(editor, editorLoaded).finally((): void => {
      form.querySelectorAll<HTMLButtonElement>('[data-reactions-dry-run-submit]').forEach((button: HTMLButtonElement): void => {
        button.disabled = false;
      });
    });
  }

  private async whenEditorIsReady(editor: CodeMirrorElement | null, editorLoaded: Promise<unknown>): Promise<void> {
    if (editor === null) {
      return;
    }
    try {
      await editorLoaded;
    } catch (error: unknown) {
      console.warn('The code editor could not be loaded, the payload is edited as plain text.', error);
      return;
    }
    const view = editor.ownerDocument.defaultView;
    const deadline = Date.now() + TryOutElement.editorTimeout;
    while (editor.editorView === null && editor.isConnected && Date.now() < deadline) {
      await new Promise((resolve): void => { view.requestAnimationFrame(resolve); });
    }
  }

  private async sendDryRun(form: HTMLFormElement, button: HTMLButtonElement): Promise<void> {
    const result = form.querySelector<HTMLElement>('[data-reactions-dry-run-result]');
    button.disabled = true;
    if (result !== null) {
      // Created through the document of the modal, which is where the spinner element is
      // defined.
      const spinner = result.ownerDocument.createElement('typo3-backend-spinner');
      spinner.setAttribute('size', 'large');
      result.replaceChildren(spinner);
      result.setAttribute('aria-busy', 'true');
    }
    try {
      const response = await new AjaxRequest(form.dataset.reactionsDryRun).post(new FormData(form));
      if (result !== null) {
        result.innerHTML = await response.resolve();
      }
    } catch {
      result?.replaceChildren();
      Notification.error(form.dataset.reactionsDryRunError ?? '');
    } finally {
      result?.removeAttribute('aria-busy');
      button.disabled = false;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-reactions-try-out': TryOutElement;
  }
}
