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

import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Icons from '@typo3/backend/icons';
import Viewport from '@typo3/backend/viewport';
import {ModuleStateStorage} from '@typo3/backend/storage/module-state-storage';

enum Selectors {
  containerSelector = '#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem',
  closeSelector = '.t3js-topbar-opendocs-close',
  menuContainerSelector = '.dropdown-menu',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
  openDocumentsItemsSelector = '.t3js-topbar-opendocs-item',
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

  /**
   * Updates the number of open documents in the toolbar according to the
   * number of items in the menu bar.
   */
  private static updateNumberOfDocs(): void {
    const num: number = $(Selectors.containerSelector).find(Selectors.openDocumentsItemsSelector).length;
    $(Selectors.counterSelector).text(num).toggle(num > 0);
  }

  constructor() {
    document.addEventListener(
      'typo3:opendocs:updateRequested',
      (evt: CustomEvent) => this.updateMenu(),
    );
    Viewport.Topbar.Toolbar.registerEvent((): void => {
      this.initializeEvents();
      this.updateMenu();
    });
  }

  /**
   * Displays the menu and does the AJAX call to the TYPO3 backend
   */
  public updateMenu(): void {
    let $toolbarItemIcon = $(Selectors.toolbarIconSelector, Selectors.containerSelector);
    let $existingIcon = $toolbarItemIcon.clone();

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      $toolbarItemIcon.replaceWith(spinner);
    });

    (new AjaxRequest(TYPO3.settings.ajaxUrls.opendocs_menu)).get().then(async (response: AjaxResponse): Promise<any> => {
      $(Selectors.containerSelector).find(Selectors.menuContainerSelector).html(await response.resolve());
      OpendocsMenu.updateNumberOfDocs();
    }).finally((): void => {
      // Re-open the menu after closing a document
      $(Selectors.toolbarIconSelector, Selectors.containerSelector).replaceWith($existingIcon);
    });
  }

  private initializeEvents(): void {
    // send a request when removing an opendoc
    $(Selectors.containerSelector).on('click', Selectors.closeSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const md5 = $(evt.currentTarget).data(this.hashDataAttributeName);
      this.closeDocument(md5);
    }).on('click', Selectors.entrySelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();

      const $entry = $(evt.currentTarget);
      this.toggleMenu();

      ModuleStateStorage.updateWithCurrentMount('web', $entry.data('pid'), true);
      const router = document.querySelector('typo3-backend-module-router');
      router.setAttribute('endpoint', $entry.attr('href'))
    });
  }

  /**
   * Closes an open document
   */
  private closeDocument(md5sum?: string): void {
    const payload: {[key: string]: string} = {};
    if (md5sum) {
      payload.md5sum = md5sum;
    }
    (new AjaxRequest(TYPO3.settings.ajaxUrls.opendocs_closedoc)).post(payload).then(async (response: AjaxResponse): Promise<any> => {
      $(Selectors.menuContainerSelector, Selectors.containerSelector).html(await response.resolve());
      OpendocsMenu.updateNumberOfDocs();
      // Re-open the menu after closing a document
      $(Selectors.containerSelector).toggleClass('open');
    });
  }

  /**
   * closes the menu (e.g. when clicked on an item)
   */
  private toggleMenu = (): void => {
    $('.scaffold').removeClass('scaffold-toolbar-expanded');
    $(Selectors.containerSelector).toggleClass('open');
  }
}

let opendocsMenuObject: OpendocsMenu;
opendocsMenuObject = new OpendocsMenu();

if (typeof TYPO3 !== 'undefined') {
  TYPO3.OpendocsMenu = opendocsMenuObject;
}

export default opendocsMenuObject;
