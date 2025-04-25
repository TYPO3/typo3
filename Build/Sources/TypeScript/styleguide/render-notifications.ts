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

import Notification from '@typo3/backend/notification';
import ImmediateAction from '@typo3/backend/action-button/immediate-action';
import DeferredAction from '@typo3/backend/action-button/deferred-action';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Action used when an operation execution time is unknown.
 */
class RenderNotifications {
  constructor() {
    this.registerEvents();
  }

  private registerEvents() {
    new RegularEvent('click', (e: MouseEvent, target: HTMLButtonElement): void => {
      const severity = target.dataset.severity as 'notice' | 'info' | 'success' | 'warning' | 'error';
      const title = target.dataset.title;
      const message = target.dataset.message;
      const duration = parseInt(target.dataset.duration, 10);
      const includeActions = target.dataset.includeActions === '1';

      Notification[severity](title, message, duration, this.createActions(includeActions));
    }).delegateTo(document, 'button[data-action="trigger-notification"]');
  }

  private createActions(includeActions: boolean) {
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
