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
import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

let itemProcessing = 0;

function setButtonStates(scope: HTMLElement, action: string): void {
  for (const _button of scope.children) {
    if (_button.nodeType !== 1 || _button.nodeName !== 'BUTTON') {
      continue;
    }

    const button = _button as HTMLElement;
    if (button.dataset.generatorAction === action) {
      button.classList.remove('disabled');
      button.hidden = false;
      button.querySelector('typo3-backend-icon').identifier = 'actions-' + action;
    } else {
      button.classList.add('disabled');
      button.hidden = true;
    }
  }
}

new RegularEvent('click', (e: MouseEvent, target: HTMLButtonElement): void => {
  e.preventDefault();

  for (const children of target.parentElement.children) {
    if (children.nodeType === 1) {
      children.classList.add('disabled');
    }
  }
  target.querySelector('typo3-backend-icon').identifier = 'spinner-circle';

  NProgress.start();
  itemProcessing++;

  // Trigger generate action
  new AjaxRequest(target.dataset.href).get().then(async (response: AjaxResponse): Promise<void> => {
    const json = await response.resolve('application/json');
    if (json.status === false) {
      NProgress.done();
      Notification.error(json.title, json.body, 5);
      target.querySelector('typo3-backend-icon').identifier = 'actions-' + target.dataset.generatorAction;
      target.classList.remove('disabled');
      return;
    }
    itemProcessing--;
    Notification.showMessage(json.title, json.body, json.status, 5);
    // Hide nprogress only if all items done loading/processing
    if (itemProcessing === 0) {
      NProgress.done();
    }
    // Set button states
    setButtonStates(target.parentElement, target.dataset.generatorAction === 'plus' ? 'delete' : 'plus');
  }).catch((error: AjaxResponse): void => {
    // Action failed, reset to its original state
    NProgress.done();
    Notification.error('', error.response.status + ' ' + error.response.statusText, 5);
    target.querySelector('typo3-backend-icon').identifier = 'actions-' + target.dataset.generatorAction;
    target.classList.remove('disabled');
  });
}).delegateTo(document, '.t3js-generator-action');
