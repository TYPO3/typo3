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

import * as $ from 'jquery';
import Notification = require('TYPO3/CMS/Backend/Notification');
import Icons = require('TYPO3/CMS/Backend/Icons');

enum Identifiers {
  clearCache = '.t3js-clear-page-cache',
  icon = '.t3js-icon',
}

/**
 * Module: TYPO3/CMS/Recordlist/ClearCache
 */
class ClearCache {
  constructor() {
    $(() => {
      this.registerClickHandler();
    });
  }

  private registerClickHandler(): void {
    $(Identifiers.clearCache + ':not([disabled])').on('click', (event: JQueryEventObject): void => {
      event.preventDefault();
      const $me = $(event.currentTarget);
      const id = parseInt($me.data('id'), 10);
      const $iconElement = $me.find(Identifiers.icon);

      $me.prop('disabled', true).addClass('disabled');
      Icons.getIcon('spinner-circle-dark', Icons.sizes.small, null, 'disabled').done((icon: string): void => {
        $iconElement.replaceWith(icon);
      });

      this.sendClearCacheRequest(id).always((): void => {
        Icons.getIcon('actions-system-cache-clear', Icons.sizes.small).done((icon: string): void => {
          $me.find(Identifiers.icon).replaceWith(icon);
        });
        $me.prop('disabled', false).removeClass('disabled');
      });
    });
  }

  /**
   * Send an AJAX request to clear a page's cache
   *
   * @param {number} pageId
   */
  private sendClearCacheRequest(pageId: number): JQueryXHR {
    return $.ajax({
      url: TYPO3.settings.ajaxUrls.web_list_clearpagecache,
      data: {
        id: pageId,
      },
      cache: false,
      dataType: 'json',
      success: (data: any): void => {
        if (data.success === true) {
          Notification.success(data.title, data.message, 1);
        } else {
          Notification.error(data.title, data.message, 1);
        }
      },
      error: (): void => {
        Notification.error(
          'Clearing page caches went wrong on the server side.',
        );
      },
    });
  }
}

export = new ClearCache();
