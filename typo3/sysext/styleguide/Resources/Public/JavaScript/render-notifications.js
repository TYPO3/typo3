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

import Notification from '@typo3/backend/notification.js';
import ImmediateAction from '@typo3/backend/action-button/immediate-action.js';
import DeferredAction from '@typo3/backend/action-button/deferred-action.js';

/**
 * Action used when an operation execution time is unkown.
 */
class RenderNotifications {
  constructor() {
    this.registerEvents()
  }

  registerEvents() {
    const _this = this;
    document.addEventListener('click', function (e) {
      if (e.target.matches('button[data-action="trigger-notification"]')) {
        const severity = e.target.dataset.severity;
        const title = e.target.dataset.title;
        const message = e.target.dataset.message;
        const duration = parseInt(e.target.dataset.duration, 10);
        const includeActions = e.target.dataset.includeActions === '1';

        if (typeof Notification[severity] === 'function') {
          Notification[severity](title, message, duration, _this.createActions(includeActions));
        }
      }
    });
  }

  createActions(includeActions) {
    if (!includeActions) {
      return [];
    }

    return [
      {
        label: 'Immediate action',
        action: new ImmediateAction(function () {
          alert('Immediate action done');
        }),
      },
      {
        label: 'Deferred action',
        action: new DeferredAction(function () {
          return new Promise(resolve => setTimeout(() => {
            alert('Deferred action done after 3000 ms');
            resolve();
          }, 3000));
        }),
      }
    ];
  }
}

export default new RenderNotifications();
