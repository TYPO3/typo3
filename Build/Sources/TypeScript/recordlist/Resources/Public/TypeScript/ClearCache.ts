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

import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import Notification = require('TYPO3/CMS/Backend/Notification');
import Icons = require('TYPO3/CMS/Backend/Icons');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');

enum Identifiers {
  clearCache = '.t3js-clear-page-cache',
  icon = '.t3js-icon',
}

/**
 * Module: TYPO3/CMS/Recordlist/ClearCache
 */
class ClearCache {
  private static setDisabled(element: HTMLButtonElement, isDisabled: boolean): void {
    element.disabled = isDisabled;
    element.classList.toggle('disabled', isDisabled);
  }

  /**
   * Send an AJAX request to clear a page's cache
   *
   * @param {number} pageId
   * @return Promise<AjaxResponse>
   */
  private static sendClearCacheRequest(pageId: number): Promise<AjaxResponse> {
    const request = new AjaxRequest(TYPO3.settings.ajaxUrls.web_list_clearpagecache).withQueryArguments({id: pageId}).get({cache: 'no-cache'});
    request.then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.success === true) {
        Notification.success(data.title, data.message, 1);
      } else {
        Notification.error(data.title, data.message, 1);
      }
    }, (): void => {
      Notification.error(
        'Clearing page caches went wrong on the server side.',
      );
    });

    return request;
  }

  constructor() {
    this.registerClickHandler();
  }

  private registerClickHandler(): void {
    const trigger = document.querySelector(`${Identifiers.clearCache}:not([disabled])`);
    if (trigger !== null) {
      new RegularEvent('click', (e: Event): void => {
        e.preventDefault();

        // The action trigger behaves like a button
        const me = e.currentTarget as HTMLButtonElement;
        const id = parseInt(me.dataset.id, 10);
        ClearCache.setDisabled(me, true);

        Icons.getIcon('spinner-circle-dark', Icons.sizes.small, null, 'disabled').then((icon: string): void => {
          me.querySelector(Identifiers.icon).outerHTML = icon;
        });

        ClearCache.sendClearCacheRequest(id).finally((): void => {
          Icons.getIcon('actions-system-cache-clear', Icons.sizes.small).then((icon: string): void => {
            me.querySelector(Identifiers.icon).outerHTML = icon;
          });
          ClearCache.setDisabled(me, false);
        })
      }).bindTo(trigger);
    }
  }
}

export = new ClearCache();
