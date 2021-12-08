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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import Icons from '../Icons';
import Notification from '../Notification';
import Viewport from '../Viewport';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem',
  menuItemSelector = 'a.toolbar-cache-flush-action',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
}

/**
 * Module: TYPO3/CMS/Backend/Toolbar/ClearCacheMenu
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
    $(Identifiers.containerSelector).on('click', Identifiers.menuItemSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const ajaxUrl = $(evt.currentTarget).attr('href');
      if (ajaxUrl) {
        this.clearCache(ajaxUrl);
      }
    });
  }

  /**
   * Calls TYPO3 to clear a cache, then changes the topbar icon
   * to a spinner. Restores the original topbar icon when the request completed.
   *
   * @param {string} ajaxUrl The URL to load
   */
  private clearCache(ajaxUrl: string): void {
    // Close clear cache menu
    $(Identifiers.containerSelector).removeClass('open');

    const $toolbarItemIcon = $(Identifiers.toolbarIconSelector, Identifiers.containerSelector);
    const $existingIcon = $toolbarItemIcon.clone();

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      $toolbarItemIcon.replaceWith(spinner);
    });

    (new AjaxRequest(ajaxUrl)).post({}).then(
      async (response: AjaxResponse): Promise<any> => {
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
      $(Identifiers.toolbarIconSelector, Identifiers.containerSelector).replaceWith($existingIcon);
    });
  }
}

export default new ClearCacheMenu();
