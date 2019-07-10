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

/**
 * Module: TYPO3/CMS/Recordlist/ClearCache
 * Folder selection
 * @exports TYPO3/CMS/Recordlist/ClearCache
 */
class ClearCache {
  constructor() {
    $(() => {
      $('.t3js-clear-page-cache').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        const $me = $(event.currentTarget);
        const id = $me.data('id');
        $.ajax({
          url: TYPO3.settings.ajaxUrls.web_list_clearpagecache + '&id=' + id,
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
      });
    });
  }
}

export = new ClearCache();
