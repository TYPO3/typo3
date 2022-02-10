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

import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';
import DebounceEvent from '@typo3/core/event/debounce-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';

interface PositionMapArguments {
  url: string,
  defVals: Array<any>,
  saveAndClose: boolean
}

enum ClassNames {
  wizardWindow = 't3-new-content-element-wizard-window'
}

enum Selectors {
  modalBodySelector = '.t3js-modal-body',
  modalTabsSelector = '.t3js-tabs',
  elementsFilterSelector = '.t3js-contentWizard-search',
  noResultSelector = '.t3js-filter-noresult',
  wizardWindowSelector = '.t3-new-content-element-wizard-window',
  wizardElementSelector = '.t3js-media-new-content-element-wizard',
  wizardElementWithTargetSelector = 'button[data-target]',
  wizardElementWithPositionMapArugmentsSelector = 'button[data-position-map-arguments]'
}

/**
 * Module: @typo3/backend/new-content-element-wizard
 */
export class NewContentElementWizard {
  private readonly modal: HTMLElement;
  private readonly elementsFilter: HTMLInputElement;

  private static getTabIdentifier(tab: Element): string {
    const tabLink = tab.querySelector('a') as HTMLAnchorElement;
    const [, tabIdentifier] = tabLink.href.split('#');
    return tabIdentifier;
  }

  private static countVisibleContentElements(container: Element): number {
    return container.querySelectorAll(Selectors.wizardElementSelector + ':not(.hidden)').length;
  }

  constructor(modal: HTMLElement) {
    this.modal = modal;
    this.elementsFilter = this.modal.querySelector(Selectors.elementsFilterSelector);

    // Add new content element specific class to the modal body
    this.modal.querySelector(Selectors.modalBodySelector)?.classList.add(ClassNames.wizardWindow);

    this.registerEvents();
  }

  private registerEvents(): void {
    new RegularEvent('shown.bs.modal', (): void => {
      this.elementsFilter.focus();
    }).bindTo(this.modal);

    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      const target = e.target as HTMLInputElement;
      if (e.code === 'Escape') {
        e.stopImmediatePropagation();
        target.value = '';
      }
    }).bindTo(this.elementsFilter);

    new DebounceEvent('keyup', (e: KeyboardEvent): void => {
      this.filterElements(e.target as HTMLInputElement);
    }, 150).bindTo(this.elementsFilter);

    new RegularEvent('search', (e: Event): void => {
      this.filterElements(e.target as HTMLInputElement);
    }).bindTo(this.elementsFilter);

    new RegularEvent('click', (e: PointerEvent): void => {
      e.preventDefault();
      e.stopPropagation();
    }).delegateTo(this.modal, [Selectors.modalTabsSelector, '.disabled'].join(' '));

    new RegularEvent('click', (e: PointerEvent, eventTarget: HTMLButtonElement): void => {
      e.preventDefault();
      const target: string = eventTarget.dataset.target;
      if (!target) {
        // Skip in case no target defined
        return;
      }
      // Close modal and call target
      Modal.dismiss();
      top.list_frame.location.href = target;
    }).delegateTo(this.modal, [Selectors.wizardWindowSelector, Selectors.wizardElementWithTargetSelector].join(' '));

    new RegularEvent('click', (e: PointerEvent, eventTarget: HTMLButtonElement): void => {
      e.preventDefault();
      if (!eventTarget.dataset.positionMapArguments) {
        // In case parameters are empty, skip this item
        return;
      }
      const positionMapArguments: PositionMapArguments = JSON.parse(eventTarget.dataset.positionMapArguments);
      if (!positionMapArguments.url) {
        // In case no url is given in the parameters, skip the item
        return;
      }
      (new AjaxRequest(positionMapArguments.url)).post({
        defVals: positionMapArguments.defVals,
        saveAndClose: positionMapArguments.saveAndClose ? '1' : '0'
      }).then(async (response: AjaxResponse): Promise<any> => {
        this.modal.querySelector(Selectors.wizardWindowSelector).innerHTML = await response.raw().text();
      }).catch((): void => {
        Notification.error('Could not load module data');
      });
    }).delegateTo(this.modal, [Selectors.wizardWindowSelector, Selectors.wizardElementWithPositionMapArugmentsSelector].join(' '));
  }

  private filterElements(inputField: HTMLInputElement): void {
    const tabContainer = this.modal.querySelector(Selectors.modalTabsSelector);
    const nothingFoundAlert = this.modal.querySelector(Selectors.noResultSelector);

    this.modal.querySelectorAll(Selectors.wizardElementSelector).forEach((element: Element): void => {
      // Clean up textContent by trimming and replacing consecutive spaces with a single space
      const textContent = element.textContent.trim().replace(/\s+/g, ' ');
      element.classList.toggle('hidden', inputField.value !== '' && !RegExp(inputField.value, 'i').test(textContent));
    });

    const visibleContentElements = NewContentElementWizard.countVisibleContentElements(this.modal);
    tabContainer.parentElement.classList.toggle('hidden', visibleContentElements === 0);
    nothingFoundAlert.classList.toggle('hidden', visibleContentElements > 0);
    this.switchTabIfNecessary(tabContainer);
  }

  private switchTabIfNecessary(tabContainer: Element): void {
    const currentActiveTab = tabContainer.querySelector('.active').parentElement;
    const siblings = Array.from(currentActiveTab.parentElement.children);

    for (let sibling of siblings) {
      const siblingTabIdentifier = NewContentElementWizard.getTabIdentifier(sibling);
      const navLink = sibling.querySelector('a');
      navLink.classList.toggle('disabled', !this.hasTabContent(siblingTabIdentifier));
      if (navLink.classList.contains('disabled')) {
        navLink.setAttribute('tabindex', '-1');
      } else {
        navLink.removeAttribute('tabindex');
      }
    }

    if (!this.hasTabContent(NewContentElementWizard.getTabIdentifier(currentActiveTab))) {
      for (let sibling of siblings) {
        if (sibling === currentActiveTab) {
          // We already know the current active tab has no content, that's why we're here in the first place
          continue;
        }

        const siblingTabIdentifier = NewContentElementWizard.getTabIdentifier(sibling);
        if (this.hasTabContent(siblingTabIdentifier)) {
          this.switchTab(tabContainer.parentElement, siblingTabIdentifier);
          break;
        }
      }
    }
  }

  private hasTabContent(tabIdentifier: string): boolean {
    const tabContentContainer = this.modal.querySelector(`#${tabIdentifier}`);
    return NewContentElementWizard.countVisibleContentElements(tabContentContainer) > 0;
  }

  /**
   * Switches the tab to the requested one. Unfortunately, bootstrap has a bug and searches the tab content in document,
   * whereas top.document is our correct context.
   *
   * @param {HTMLElement} tabContainerWrapper
   * @param {string} tabIdentifier
   */
  private switchTab(tabContainerWrapper: HTMLElement, tabIdentifier: string): void {
    const tabElement = tabContainerWrapper.querySelector(`a[href="#${tabIdentifier}"]`);
    const tabContentElement = this.modal.querySelector(`#${tabIdentifier}`);

    tabContainerWrapper.querySelector('a.active').classList.remove('active');
    tabContainerWrapper.querySelector('.tab-pane.active').classList.remove('active');

    tabElement.classList.add('active');
    tabContentElement.classList.add('active');
  }
}
