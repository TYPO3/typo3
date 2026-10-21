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

import FormEngine from '@typo3/backend/form-engine';
import type { Consumable } from '@typo3/backend/event/consumable';
import type TriggerRequest from '@typo3/backend/event/trigger-request';
import Modal, { type ModalElement } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import DocumentService from '@typo3/core/document-service';
import { topDocument, topWindow } from '@typo3/core/utility/top-window';
import '@typo3/backend/element/icon-element';
import labels from '~labels/backend.alt_doc';
import coreCommonLabels from '~labels/core.common';

type ContextualRecordEditOptions = {
  justSaved?: boolean;
  savedRecordTitle?: string;
  closed?: boolean;
};

/**
 * Module: @typo3/backend/contextual-record-edit
 *
 * Handles communication between a FormEngine form rendered in contextual mode
 * (inside a modal iframe) and the parent frame. Wires up Save/Close
 * buttons and sends postMessage events to the parent on save and close.
 *
 * @exports @typo3/backend/contextual-record-edit
 */
class ContextualRecordEdit {
  private closeButton: HTMLButtonElement | null = null;
  private fullscreenButton: HTMLAnchorElement | null = null;
  private readonly options: ContextualRecordEditOptions;
  // "<table>:<uid>" of the edited record
  private readonly record: string = ContextualRecordEdit.resolveRecord();

  constructor(options: ContextualRecordEditOptions = {}) {
    this.options = options;
    DocumentService.ready().then((): void => {
      this.closeButton = document.querySelector('.t3js-contextual-close');
      this.fullscreenButton = document.querySelector('.t3js-contextual-fullscreen');
      this.initialize();
    });
  }

  private static resolveRecord(): string {
    for (const key of new URL(window.location.href).searchParams.keys()) {
      const match = key.match(/^edit\[([^\]]+)]\[(\d+)]$/);
      if (match !== null) {
        return match[1] + ':' + match[2];
      }
    }
    return '';
  }

  private initialize(): void {
    this.initializeCloseButton();
    this.initializeFullscreenButton();
    this.initializeEscapeKey();
    this.initializeParentMessages();
    this.initializeNavigationGuard();
    this.handlePostSaveState();
    if (!this.options.justSaved) {
      this.focusFirstElement();
    }
  }

  private initializeCloseButton(): void {
    if (!this.closeButton) {
      return;
    }
    this.closeButton.addEventListener('click', (): void => {
      this.requestClose();
    });
  }

  private initializeFullscreenButton(): void {
    if (!this.fullscreenButton) {
      return;
    }
    this.fullscreenButton.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      // Navigate the content frame directly (same-origin access), using
      // postMessage for this failed silently in Firefox. The navigation
      // guard asks to discard unsaved changes and closes the sheet.
      FormEngine.ready().then((): void => {
        topWindow.TYPO3?.Backend?.ContentContainer?.setUrl(this.fullscreenButton.href);
      });
    });
  }

  /**
   * Navigating the content frame while the sheet is open (e.g. via a context
   * menu action) would load the target behind the sheet. The sheet therefore
   * replaces FormEngine as navigation guard and closes itself instead.
   */
  private initializeNavigationGuard(): void {
    const consumerScope = topWindow.TYPO3?.Backend?.consumerScope;
    if (!consumerScope) {
      return;
    }
    const navigationGuard: Consumable = {
      consume: (interactionRequest: TriggerRequest): Promise<void> | null => {
        if (!interactionRequest.concernsTypes(['typo3.setUrl'])) {
          return null;
        }
        // Stays pending if the user keeps editing, which cancels the navigation
        return new Promise<void>((resolve): void => {
          if (interactionRequest.concernsTypes(['typo3.recordCommit'])) {
            // A record commit returns to the current module, so the sheet stays
            // open and is reloaded once the commit is done to show its result.
            this.confirmDiscardChanges((): void => {
              topDocument.addEventListener('typo3-module-loaded', () => this.reload(), { once: true });
              resolve();
            });
            return;
          }
          this.confirmDiscardChanges((): void => {
            resolve();
            window.parent.postMessage(
              { actionName: 'typo3:editform:navigate' },
              window.location.origin
            );
          });
        });
      },
    };
    let pageHidden = false;
    window.addEventListener('pagehide', (): void => {
      pageHidden = true;
      consumerScope.detach(navigationGuard);
    }, { once: true });
    FormEngine.ready().then((): void => {
      if (pageHidden) {
        return;
      }
      consumerScope.detach(FormEngine);
      consumerScope.attach(navigationGuard);
    });
  }

  private reload(): void {
    const url = new URL(window.location.href);
    url.searchParams.delete('justSaved');
    window.location.replace(url.toString());
  }

  private confirmDiscardChanges(onDiscard: () => void, onSave: (() => void) | null = null): void {
    if (!FormEngine.hasChange()) {
      onDiscard();
      return;
    }
    Modal.confirm(
      labels.get('label.confirm.close_without_save.title'),
      labels.get('label.confirm.close_without_save.content'),
      Severity.warning,
      [
        {
          text: labels.get('buttons.confirm.close_without_save.no'),
          btnClass: 'btn-default',
          trigger: (_e, modal): void => { modal.hideModal(); },
        },
        {
          text: labels.get('buttons.confirm.close_without_save.yes'),
          btnClass: 'btn-default',
          trigger: (_e, modal): void => { modal.hideModal(); onDiscard(); },
        },
        ...(onSave !== null && document.querySelector('.has-error') === null ? [{
          text: coreCommonLabels.get('save'),
          btnClass: 'btn-primary',
          active: true,
          trigger: (_e: Event, modal: ModalElement): void => { modal.hideModal(); onSave(); },
        }] : []),
      ]
    );
  }

  private initializeEscapeKey(): void {
    document.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Escape') {
        e.preventDefault();
        this.requestClose();
      }
    });
  }

  private initializeParentMessages(): void {
    window.addEventListener('message', (event: MessageEvent): void => {
      if (event.origin !== window.location.origin) {
        return;
      }
      if (event.data?.actionName === 'typo3:editform:requestclose') {
        this.requestClose();
      }
      if (event.data?.actionName === 'typo3:editform:reload') {
        // Saving the stale form of the record that was just saved elsewhere would revert that change
        const canSave = event.data.savedRecord !== this.record;
        this.confirmDiscardChanges((): void => this.reload(), canSave ? (): void => FormEngine.saveDocument() : null);
      }
    });
  }

  /**
   * Check if this page load is the result of a save (PRG redirect) or a close.
   */
  private handlePostSaveState(): void {
    if (this.options.justSaved) {
      window.parent.postMessage(
        {
          actionName: 'typo3:editform:saved',
          recordTitle: this.options.savedRecordTitle ?? '',
          record: this.record,
        },
        window.location.origin
      );
      this.showSavedIndicator();
    }

    if (this.options.closed) {
      this.notifyParentClose();
    }
  }

  private requestClose(): void {
    if (FormEngine.hasChange()) {
      Modal.confirm(
        labels.get('label.confirm.close_without_save.title'),
        labels.get('label.confirm.close_without_save.content'),
        Severity.warning,
        [
          {
            text: labels.get('buttons.confirm.close_without_save.no'),
            btnClass: 'btn-default',
            trigger: (_e, modal): void => { modal.hideModal(); },
          },
          {
            text: labels.get('buttons.confirm.close_without_save.yes'),
            btnClass: 'btn-default',
            trigger: (_e, modal): void => { modal.hideModal(); this.notifyParentClose(); },
          },
          {
            text: labels.get('buttons.confirm.save_and_close'),
            btnClass: 'btn-primary',
            active: true,
            trigger: (_e, modal): void => { modal.hideModal(); FormEngine.saveAndCloseDocument(); },
          },
        ]
      );
    } else {
      this.notifyParentClose();
    }
  }

  private showSavedIndicator(): void {
    const actions = document.querySelector('.contextual-record-edit-actions');
    if (!actions) {
      return;
    }
    const indicator = document.createElement('span');
    indicator.className = 'contextual-record-edit-saved-indicator';
    indicator.innerHTML = '<typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon> '
      + labels.get('notification.record_saved.title.singular');
    actions.prepend(indicator);
    setTimeout((): void => {
      indicator.style.opacity = '0';
      indicator.addEventListener('transitionend', () => indicator.remove());
    }, 2000);
  }

  private focusFirstElement(): void {
    const target = this.fullscreenButton ?? this.closeButton;
    target?.focus();
  }

  private notifyParentClose(): void {
    window.parent.postMessage(
      { actionName: 'typo3:editform:closed' },
      window.location.origin
    );
  }

}

export default ContextualRecordEdit;
