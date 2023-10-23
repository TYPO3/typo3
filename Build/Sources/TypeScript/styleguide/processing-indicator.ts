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

import NProgress from 'nprogress';
import Icons from '@typo3/backend/icons';
import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';

let itemProcessing = 0;

Icons.getIcon('spinner-circle', Icons.sizes.small).then((spinner: string): void => {
  new RegularEvent('click', (e: MouseEvent, target: HTMLButtonElement): void => {
    e.preventDefault();

    const originalIcon = target.querySelector('span').outerHTML;
    const disabledButton = target.parentNode.querySelector('button.disabled');

    target.querySelector('span').outerHTML = spinner;
    target.classList.add('disabled');

    NProgress.start();
    itemProcessing++;

    // Trigger generate action
    new AjaxRequest(target.dataset.href).get().then(async (response: AjaxResponse): Promise<void> => {
      const json = await response.resolve('application/json');

      itemProcessing--
      Notification.showMessage(json.title, json.body, json.status, 5);
      // Hide nprogress only if all items done loading/processing
      if (itemProcessing === 0) {
        NProgress.done();
      }
      // Set button states
      target.querySelector('.t3js-icon').outerHTML = originalIcon;
      disabledButton.classList.remove('disabled');
    }).catch((error: AjaxResponse): void => {
      // Action failed, reset to its original state
      NProgress.done();
      Notification.error('', error.response.status + ' ' + error.response.statusText, 5);

      target.querySelector('.t3js-icon').outerHTML = originalIcon;
      target.classList.remove('disabled');
    });
  }).delegateTo(document, '.t3js-generator-action');
});
