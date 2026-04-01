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
import labels from '~labels/backend.alt_doc';

/**
 * Module: @typo3/backend/element/contextual-record-edit-trigger
 *
 * A trigger that opens a record for editing in a sheet when
 * the user setting `contextualRecordEdit` is enabled and in
 * the content frame when disabled.
 *
 * `url` attribute points to the contextual edit route
 * `edit-url` points to the full FormEngine route.
 *
 * @example
 * <typo3-backend-contextual-record-edit-trigger
 *   url="/typo3/record/edit/contextual?..."
 *   edit-url="/typo3/record/edit?...">
 *   Edit record
 * </typo3-backend-contextual-record-edit-trigger>
 */
@customElement('typo3-backend-contextual-record-edit-trigger')
export class ContextualRecordEditTriggerElement extends PseudoButtonLitElement {
  @property({ type: String }) url: string;
  @property({ type: String, attribute: 'edit-url' }) editUrl: string;

  protected override async buttonActivated(): Promise<void> {
    if (Persistent.isset('contextualRecordEdit') && Persistent.get('contextualRecordEdit') == 0) {
      if (top?.TYPO3?.Backend?.ContentContainer) {
        top.TYPO3.Backend.ContentContainer.setUrl(this.editUrl);
      }
      return;
    }
    const modal = Modal.advanced({
      type: Types.iframe,
      title: '',
      content: this.url,
      size: Sizes.expand,
      position: Positions.sheet,
      hideHeader: true,
    });
    this.setupMessageHandling(modal);
  }

  private setupMessageHandling(modal: ModalElement): void {
    const messageTarget = top;
    let savedRecordTitle = '';
    let hasSaved = false;
    let closeConfirmed = false;
    const messageHandler = (event: MessageEvent): void => {
      if (event.origin !== window.location.origin) {
        return;
      }
      if (event.data?.actionName === 'typo3:editform:saved') {
        hasSaved = true;
        savedRecordTitle = event.data.recordTitle ?? '';
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
      const iframe = modal.querySelector('iframe') as HTMLIFrameElement | null;
      iframe?.contentWindow?.postMessage(
        { actionName: 'typo3:editform:requestclose' },
        window.location.origin
      );
    });

    modal.addEventListener('typo3-modal-hidden', () => {
      messageTarget.removeEventListener('message', messageHandler);
      if (hasSaved) {
        top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
        if (top.TYPO3?.Backend?.ContentContainer) {
          top.TYPO3.Backend.ContentContainer.refresh();
        }
        Notification.success(
          labels.get('notification.record_updated.title'),
          savedRecordTitle !== '' ? labels.get('notification.record_updated.message', [savedRecordTitle]) : undefined,
        );
      } else {
        this.focus();
      }
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-contextual-record-edit-trigger': ContextualRecordEditTriggerElement;
  }
}
