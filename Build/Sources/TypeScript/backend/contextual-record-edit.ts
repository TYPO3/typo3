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
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import DocumentService from '@typo3/core/document-service';
import { Tab } from 'bootstrap';
import '@typo3/backend/element/icon-element';
import labels from '~labels/backend.alt_doc';

type ContextualRecordEditOptions = {
  justSaved?: boolean;
  savedRecordTitle?: string;
  closed?: boolean;
};

/**
 * Module: @typo3/backend/contextual-record-edit
 *
 * Handles communication between a FormEngine form rendered in contextual mode
 * (inside a context panel iframe) and the parent frame. Wires up Save/Close
 * buttons and sends postMessage events to the parent on save and close.
 *
 * @exports @typo3/backend/contextual-record-edit
 */
class ContextualRecordEdit {
  private saveButton: HTMLButtonElement | null = null;
  private closeButton: HTMLButtonElement | null = null;
  private fullscreenButton: HTMLAnchorElement | null = null;
  private readonly options: ContextualRecordEditOptions;

  constructor(options: ContextualRecordEditOptions = {}) {
    this.options = options;
    DocumentService.ready().then((): void => {
      this.saveButton = document.querySelector('.t3js-contextual-save');
      this.closeButton = document.querySelector('.t3js-contextual-close');
      this.fullscreenButton = document.querySelector('.t3js-contextual-fullscreen');
      this.initialize();
    });
  }

  private initialize(): void {
    FormEngine.ready().then((): void => {
      this.observeFormChanges();
    });

    this.initializeSaveButton();
    this.initializeCloseButton();
    this.initializeFullscreenButton();
    this.initializeEscapeKey();
    this.initializeParentMessages();
    this.handlePostSaveState();
    this.initTabOverflow();
    if (!this.options.justSaved) {
      this.focusFirstElement();
    }
  }

  private initializeSaveButton(): void {
    if (!this.saveButton) {
      return;
    }
    // The button is type="submit" with form="ContextualRecordEditController" so that
    // pressing Enter in any form field triggers save (native browser behavior).
    // We intercept to go through FormEngine.saveDocument() which normalizes fields first.
    this.saveButton.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      FormEngine.saveDocument();
    });
  }

  /**
   * Enable the Save button only when the form has unsaved changes.
   *
   * Uses a MutationObserver on class attribute changes because FormEngine
   * does not dispatch events when fields are marked as changed. It applies
   * the `.has-change` CSS class in multiple code paths, so observing the
   * DOM is the most reliable way to catch all of them without modifying
   * FormEngine itself. Once FormEngine provides a dedicated change event,
   * this observer should be replaced with a simple event listener.
   */
  private observeFormChanges(): void {
    if (!this.saveButton) {
      return;
    }
    const updateSaveButton = (): void => {
      this.saveButton.disabled = !FormEngine.hasChange();
    };
    updateSaveButton();
    const observer = new MutationObserver(updateSaveButton);
    observer.observe(document.body, {
      subtree: true,
      attributes: true,
      attributeFilter: ['class'],
    });
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
      const navigate = (): void => {
        // Clear FormEngine's dirty tracking so it doesn't show its own confirmation after ours
        document.querySelectorAll('.has-change').forEach((el) => el.classList.remove('has-change'));
        // Navigate the content frame directly (same-origin access) — using
        // postMessage for this failed silently in Firefox.
        top?.TYPO3?.Backend?.ContentContainer?.setUrl(this.fullscreenButton.href);
        // Tell the parent to close the context panel
        window.parent.postMessage(
          { actionName: 'typo3:editform:navigate' },
          window.location.origin
        );
      };
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
              btnClass: 'btn-warning',
              trigger: (_e, modal): void => { modal.hideModal(); navigate(); },
            },
          ]
        );
      } else {
        navigate();
      }
    });
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

  /**
   * Collapses overflowing nav-tabs into a dropdown menu.
   * Only active inside .contextual-record-edit where tabs use flex-wrap: nowrap.
   */
  private initTabOverflow(): void {
    const navTabs = document.querySelector('.contextual-record-edit .nav-tabs') as HTMLElement | null;
    if (!navTabs) {
      return;
    }

    const allItems = Array.from(navTabs.querySelectorAll<HTMLLIElement>(':scope > .nav-item'));
    if (allItems.length <= 1) {
      return;
    }

    const moreItem = document.createElement('li');
    moreItem.className = 'nav-item dropdown nav-tabs-overflow';
    moreItem.hidden = true;
    moreItem.innerHTML =
      '<button type="button" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">'
      + '<span class="nav-tabs-overflow-label"></span>'
      + '<typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>'
      + '</button>'
      + '<ul class="dropdown-menu dropdown-menu-end"></ul>';
    navTabs.appendChild(moreItem);

    const dropdownMenu = moreItem.querySelector('.dropdown-menu') as HTMLUListElement;
    const overflowLabel = moreItem.querySelector('.nav-tabs-overflow-label') as HTMLSpanElement;

    function update(): void {
      for (const item of allItems) {
        item.hidden = false;
      }
      moreItem.hidden = true;
      dropdownMenu.innerHTML = '';

      if (navTabs.scrollWidth <= navTabs.clientWidth) {
        return;
      }

      moreItem.hidden = false;
      const moreWidth = moreItem.getBoundingClientRect().width;
      const containerLeft = navTabs.getBoundingClientRect().left;
      const availableWidth = navTabs.clientWidth - moreWidth;

      let cutoff = allItems.length;
      for (let i = 0; i < allItems.length; i++) {
        const rect = allItems[i].getBoundingClientRect();
        if ((rect.right - containerLeft) > availableWidth) {
          cutoff = i;
          break;
        }
      }

      if (cutoff >= allItems.length) {
        moreItem.hidden = true;
        return;
      }

      let activeOverflowLabel = '';
      for (let i = cutoff; i < allItems.length; i++) {
        const item = allItems[i];
        item.hidden = true;

        const tabLink = item.querySelector('.nav-link') as HTMLElement;
        const li = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dropdown-item';

        if (tabLink.classList.contains('active')) {
          button.classList.add('active');
          activeOverflowLabel = tabLink.textContent?.trim() ?? '';
        }
        if (item.classList.contains('has-validation-error')) {
          button.classList.add('dropdown-item-danger');
        }

        button.textContent = tabLink.textContent?.trim() ?? '';
        button.addEventListener('click', (): void => {
          Tab.getOrCreateInstance(tabLink).show();
          requestAnimationFrame(update);
        });

        li.appendChild(button);
        dropdownMenu.appendChild(li);
      }
      overflowLabel.textContent = activeOverflowLabel;
    }

    navTabs.addEventListener('shown.bs.tab', (): void => {
      requestAnimationFrame(update);
    });

    new ResizeObserver((): void => {
      update();
    }).observe(navTabs);

    update();
  }
}

export default ContextualRecordEdit;
