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
import { createContextPanel, Sizes, Placements, type ContextPanelElement } from '@typo3/backend/element/context-panel';
import Notification from '@typo3/backend/notification';
import Persistent from '@typo3/backend/storage/persistent';
import labels from '~labels/backend.alt_doc';

/**
 * Module: @typo3/backend/element/contextual-record-edit-trigger
 *
 * A trigger that opens a record for editing in the context panel.
 * The `url` attribute points to the contextual edit route (context
 * panel), `edit-url` points to the full FormEngine route.
 *
 * When the user setting `contextualRecordEdit` is enabled
 * (default), the record opens in the context panel via `url`.
 * Otherwise, `edit-url` is loaded in the content frame.
 *
 * The optional `placement` attribute controls the slide-in direction:
 * `end` (default, right), `start` (left), `top`, or `bottom`.
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
  @property({ type: String }) placement: string = Placements.end;

  protected override async buttonActivated(): Promise<void> {
    if (Persistent.isset('contextualRecordEdit') && Persistent.get('contextualRecordEdit') == 0) {
      if (top?.TYPO3?.Backend?.ContentContainer) {
        top.TYPO3.Backend.ContentContainer.setUrl(this.editUrl);
      }
      return;
    }
    const panel = await createContextPanel({
      url: this.url,
      size: Sizes.medium,
      placement: (this.placement as Placements) || Placements.end,
    });
    this.setupMessageHandling(panel);
  }

  private setupMessageHandling(panel: ContextPanelElement): void {
    const messageTarget = top;
    let savedRecordTitle = '';
    const messageHandler = (event: MessageEvent): void => {
      if (event.origin !== window.location.origin) {
        return;
      }
      if (event.data?.actionName === 'typo3:editform:saved') {
        savedRecordTitle = event.data.recordTitle ?? '';
      }
      if (event.data?.actionName === 'typo3:editform:closed') {
        panel.close();
      }
      if (event.data?.actionName === 'typo3:editform:navigate') {
        panel.close();
      }
    };
    messageTarget.addEventListener('message', messageHandler);

    panel.addEventListener('typo3-context-panel-close-request', (e: Event): void => {
      e.preventDefault();
      const iframe = panel.querySelector('iframe') as HTMLIFrameElement | null;
      iframe?.contentWindow?.postMessage(
        { actionName: 'typo3:editform:requestclose' },
        window.location.origin
      );
    });

    panel.addEventListener('typo3-context-panel-hidden', () => {
      messageTarget.removeEventListener('message', messageHandler);
      panel.remove();
      if (savedRecordTitle !== '') {
        top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
        if (top.TYPO3?.Backend?.ContentContainer) {
          top.TYPO3.Backend.ContentContainer.refresh();
        }
        Notification.success(
          labels.get('notification.record_updated.title'),
          labels.get('notification.record_updated.message', [savedRecordTitle]),
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
