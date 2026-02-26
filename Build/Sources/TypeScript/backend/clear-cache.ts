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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';
import Icons from '@typo3/backend/icons';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import cacheLabels from '~labels/core.cache';

enum Identifiers {
  clearCache = '.t3js-clear-page-cache',
  icon = '.t3js-icon',
}

/**
 * Module: @typo3/backend/clear-cache
 */
class ClearCache {
  constructor() {
    this.registerClickHandler();
  }

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
    const request = new AjaxRequest(TYPO3.settings.ajaxUrls.clearcache_page).post({ id: pageId });
    request.then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data?.success === false) {
        Notification.error(
          data.title ?? cacheLabels.get('notification.error.title'),
          data.message ?? cacheLabels.get('notification.error.message'),
        );
      } else {
        Notification.success(
          data?.title ?? cacheLabels.get('notification.success.title'),
          data?.message ?? cacheLabels.get('notification.success.message'),
        );
      }
    }, (): void => {
      Notification.error(
        cacheLabels.get('notification.error.title'),
        cacheLabels.get('notification.error.message'),
      );
    });

    return request;
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

        Icons.getIcon('spinner-circle', Icons.sizes.small, null, 'disabled').then((icon: string): void => {
          me.querySelector(Identifiers.icon).outerHTML = icon;
        });

        ClearCache.sendClearCacheRequest(id).finally((): void => {
          Icons.getIcon('actions-system-cache-clear', Icons.sizes.small).then((icon: string): void => {
            me.querySelector(Identifiers.icon).outerHTML = icon;
          });
          ClearCache.setDisabled(me, false);
        });
      }).bindTo(trigger);
    }
  }
}

export default new ClearCache();
