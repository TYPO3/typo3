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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import '@typo3/backend/element/spinner-element';
import Viewport from '@typo3/backend/viewport';
import { Sizes } from '@typo3/backend/enum/icon-types';
import RegularEvent from '@typo3/core/event/regular-event';

enum Selectors {
  containerSelector = '#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem',
  closeSelector = '.t3js-topbar-opendocs-close',
  menuContainerSelector = '.dropdown-menu',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
  counterSelector = '#tx-opendocs-counter',
  entrySelector = '.t3js-open-doc',
}

/**
 * Module: @typo3/opendocs/opendocs-menu
 * main JS part taking care of
 *  - navigating to the documents
 *  - updating the menu
 */
class OpendocsMenu {
  private readonly hashDataAttributeName: string = 'opendocsidentifier';

  constructor() {
    document.addEventListener('typo3:opendocs:updateRequested', () => this.updateMenu());
    Viewport.Topbar.Toolbar.registerEvent((): void => {
      this.initializeEvents();
      this.updateMenu();
    });
  }

  /**
   * Updates the number of open documents in the toolbar according to the
   * number of items in the menu bar.
   */
  private static updateNumberOfDocs(): void {
    const containerElement = document.querySelector(Selectors.containerSelector);
    const counterElement = document.querySelector(Selectors.counterSelector) as HTMLElement;

    let num: number = parseInt((containerElement.querySelector('[data-open-docs]') as HTMLElement)?.dataset.openDocs, 10);
    if (isNaN(num)) {
      num = 0;
    }

    counterElement.textContent = num.toString();
    counterElement.classList.toggle('hidden', (num === 0));
  }

  /**
   * Displays the menu and does the AJAX call to the TYPO3 backend
   */
  public updateMenu(): void {
    const toolbarItemIcon = document.querySelector(Selectors.containerSelector + ' ' + Selectors.toolbarIconSelector);
    const existingIcon = toolbarItemIcon.cloneNode(true);

    const spinner = document.createElement('typo3-backend-spinner');
    spinner.setAttribute('size', Sizes.small);
    toolbarItemIcon.replaceWith(spinner);

    (new AjaxRequest(TYPO3.settings.ajaxUrls.opendocs_menu)).get().then(async (response: AjaxResponse): Promise<void> => {
      document.querySelector(Selectors.containerSelector + ' ' + Selectors.menuContainerSelector).innerHTML = await response.resolve();
      OpendocsMenu.updateNumberOfDocs();
    }).finally((): void => {
      // Re-open the menu after closing a document
      document.querySelector(Selectors.containerSelector + ' typo3-backend-spinner').replaceWith(existingIcon);
    });
  }

  private initializeEvents(): void {
    const containerElement = document.querySelector(Selectors.containerSelector);
    // send a request when removing an opendoc
    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();
      const md5 = target.dataset[this.hashDataAttributeName];
      this.closeDocument(md5);
    }).delegateTo(containerElement, Selectors.closeSelector);

    new RegularEvent('click', (e: Event, entry: HTMLElement): void => {
      e.preventDefault();
      const router = document.querySelector('typo3-backend-module-router');
      router.setAttribute('endpoint', entry.getAttribute('href'));
    }).delegateTo(containerElement, Selectors.entrySelector);
  }

  /**
   * Closes an open document
   */
  private closeDocument(md5sum?: string): void {
    const payload: {[key: string]: string} = {};
    if (md5sum) {
      payload.md5sum = md5sum;
    }
    (new AjaxRequest(TYPO3.settings.ajaxUrls.opendocs_closedoc)).post(payload).then(async (response: AjaxResponse): Promise<void> => {
      const containerElement = document.querySelector(Selectors.containerSelector);
      containerElement.querySelector(Selectors.menuContainerSelector).innerHTML = await response.resolve();
      OpendocsMenu.updateNumberOfDocs();
    });
  }
}

export default new OpendocsMenu();
