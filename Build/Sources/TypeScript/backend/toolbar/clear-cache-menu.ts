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
import Icons from '../icons';
import Notification from '../notification';
import Viewport from '../viewport';
import RegularEvent from '@typo3/core/event/regular-event';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem',
  menuItemSelector = '.t3js-toolbar-cache-flush-action',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
}

/**
 * Module: @typo3/backend/toolbar/clear-cache-menu
 * main functionality for clearing caches via the top bar
 * reloading the clear cache icon
 */
class ClearCacheMenu {
  constructor() {
    Viewport.Topbar.Toolbar.registerEvent(this.initializeEvents);
  }

  /**
   * Registers listeners for the icons inside the dropdown to trigger
   * the clear cache call
   */
  private initializeEvents = (): void => {
    const toolbarItemContainer = document.querySelector(Identifiers.containerSelector);

    new RegularEvent('click', (e: Event, menuItem: HTMLAnchorElement): void => {
      e.preventDefault();
      if (menuItem.href) {
        this.clearCache(menuItem.href);
      }
    }).delegateTo(toolbarItemContainer, Identifiers.menuItemSelector);
  };

  /**
   * Calls TYPO3 to clear a cache, then changes the topbar icon
   * to a spinner. Restores the original topbar icon when the request completed.
   *
   * @param {string} ajaxUrl The URL to load
   */
  private clearCache(ajaxUrl: string): void {
    const toolbarItemContainer = document.querySelector(Identifiers.containerSelector);
    // Close clear cache menu
    toolbarItemContainer.classList.remove('open');

    const toolbarItemIcon = toolbarItemContainer.querySelector(Identifiers.toolbarIconSelector);
    const existingIcon = toolbarItemIcon.cloneNode(true);

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      toolbarItemIcon.replaceWith(document.createRange().createContextualFragment(spinner));
    });

    (new AjaxRequest(ajaxUrl)).post({}).then(
      async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          Notification.success(data.title, data.message);
        } else if (data.success === false) {
          Notification.error(data.title, data.message);
        }
      },
      (): void => {
        Notification.error(TYPO3.lang['flushCaches.error'], TYPO3.lang['flushCaches.error.description']);
      },
    ).finally((): void => {
      toolbarItemContainer.querySelector(Identifiers.toolbarIconSelector).replaceWith(existingIcon);
    });
  }
}

export default new ClearCacheMenu();
