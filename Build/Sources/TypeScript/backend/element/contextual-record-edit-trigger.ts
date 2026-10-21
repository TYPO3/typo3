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
import Modal, { Types, Sizes, Positions, type ModalElement } from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import Persistent from '@typo3/backend/storage/persistent';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import labels from '~labels/backend.alt_doc';
import { topWindow, topDocument } from '@typo3/core/utility/top-window';

/**
 * Module: @typo3/backend/element/contextual-record-edit-trigger
 *
 * A trigger that opens a record for editing in a sheet when
 * the user setting `contextualRecordEdit` is enabled and in
 * the content frame when disabled.
 *
 * `url` attribute points to the contextual edit route
 * `edit-url` points to the full FormEngine route.
 * `context` identifies what's being edited, used to persist the sheet's
 * width independently per context. This might be just the database table
 * (e.g. "pages" and "sys_category" are resized independently), but callers
 * may pass a more specific value later, e.g. "tt_content.textmedia" vs.
 * "tt_content.header", once per-CType widths are needed.
 *
 * @example
 * <typo3-backend-contextual-record-edit-trigger
 *   url="/typo3/record/edit/contextual?..."
 *   edit-url="/typo3/record/edit?..."
 *   context="pages">
 *   Edit record
 * </typo3-backend-contextual-record-edit-trigger>
 */
/**
 * Whether a contextual record edit sheet is currently open in the backend.
 */
export const isContextualRecordEditOpen = (): boolean => {
  return topDocument.querySelector('typo3-backend-modal[data-contextual-record-edit]') !== null;
};

/**
 * Opens the contextual record edit route in a sheet. Sheets may be nested,
 * each one only reacts to messages of its own iframe.
 */
export const openContextualRecordEdit = (url: string, context: string = '', focusTarget: HTMLElement | null = null): ModalElement => {
  const modal = Modal.advanced({
    type: Types.iframe,
    title: '',
    content: url,
    size: Sizes.expand,
    position: Positions.sheet,
    hideHeader: true,
    resizeIdentifier: `contextualEdit${context ? `.${context}` : ''}`,
  });
  modal.setAttribute('data-contextual-record-edit', '');
  setupMessageHandling(modal, focusTarget);
  return modal;
};

const setupMessageHandling = (modal: ModalElement, focusTarget: HTMLElement | null): void => {
  const messageTarget = topWindow;
  let savedRecordTitle = '';
  let savedRecord = '';
  let hasSaved = false;
  let closeConfirmed = false;
  const messageHandler = (event: MessageEvent): void => {
    if (event.origin !== window.location.origin) {
      return;
    }
    if (event.source === null || event.source !== modal.querySelector('iframe')?.contentWindow) {
      return;
    }
    if (event.data?.actionName === 'typo3:editform:saved') {
      hasSaved = true;
      savedRecordTitle = event.data.recordTitle ?? '';
      savedRecord = event.data.record ?? '';
    }
    if (event.data?.actionName === 'typo3:editform:closed') {
      closeConfirmed = true;
      modal.hideModal();
    }
    if (event.data?.actionName === 'typo3:editform:navigate') {
      closeConfirmed = true;
      modal.hideModal();
    }
  };
  messageTarget.addEventListener('message', messageHandler);

  modal.addEventListener('typo3-modal-hide', (e: Event): void => {
    if (closeConfirmed) {
      return;
    }
    e.preventDefault();
    const iframe = modal.querySelector('iframe');
    iframe?.contentWindow?.postMessage(
      { actionName: 'typo3:editform:requestclose' },
      window.location.origin
    );
  });

  modal.addEventListener('typo3-modal-hidden', (): void => {
    messageTarget.removeEventListener('message', messageHandler);
    if (hasSaved) {
      // A sheet below may show the saved record, e.g. as a relation
      const sheets = topDocument.querySelectorAll<ModalElement>('typo3-backend-modal[data-contextual-record-edit]');
      sheets.item(sheets.length - 1)?.querySelector('iframe')?.contentWindow?.postMessage(
        { actionName: 'typo3:editform:reload', savedRecord },
        window.location.origin
      );
      topDocument.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
      if (topWindow.TYPO3?.Backend?.ContentContainer) {
        topWindow.TYPO3.Backend.ContentContainer.refresh();
      }
      Notification.success(
        labels.get('notification.record_updated.title'),
        savedRecordTitle !== '' ? labels.get('notification.record_updated.message', [savedRecordTitle]) : undefined,
      );
    } else {
      focusTarget?.focus();
    }
  });
};

@customElement('typo3-backend-contextual-record-edit-trigger')
export class ContextualRecordEditTriggerElement extends PseudoButtonLitElement {
  @property({ type: String }) url: string;
  @property({ type: String, attribute: 'edit-url' }) editUrl: string;
  @property({ type: String }) context: string = '';

  protected override async buttonActivated(): Promise<void> {
    if (Persistent.isset('contextualRecordEdit') && Persistent.get('contextualRecordEdit') == 0) {
      if (topWindow.TYPO3?.Backend?.ContentContainer) {
        topWindow.TYPO3.Backend.ContentContainer.setUrl(this.editUrl);
      }
      return;
    }
    // The sheet's handlers must live in the top window, since the content
    // frame this trigger is rendered in may be reloaded while the sheet is open.
    const { openContextualRecordEdit: open } = topWindow === window
      ? { openContextualRecordEdit }
      : await topLevelModuleImport('@typo3/backend/element/contextual-record-edit-trigger.js');
    open(this.url, this.context, this);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-contextual-record-edit-trigger': ContextualRecordEditTriggerElement;
  }
}
