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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { default as Modal } from '@typo3/backend/modal';

/**
 * Module: @typo3/filelist/create-folder
 * @exports @typo3/filelist/create-folder
 */
export default class CreateFolder {
  constructor() {
    DocumentService.ready().then((): void => {
      new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
        e.preventDefault();

        const url = new URL(target.href, window.location.origin);
        url.searchParams.set('contentOnly', '1');

        (new AjaxRequest(url.toString())).get()
          .then((response: AjaxResponse) => response.resolve())
          .then((response) => {
            const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
            contentContainer.innerHTML = response;
          });
      }).delegateTo(document, '[data-filelist-action="list-folders"]');

      new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
        e.preventDefault();

        top.list_frame.document.location.href = target.href;
        Modal.currentModal.addEventListener('typo3-modal-hide', (modalEvent: Event): void => {
          // stop event propagation to avoid `typo3-modal-hidden` being triggered
          modalEvent.stopImmediatePropagation();
        });
        Modal.dismiss();
      }).delegateTo(document, '[data-filelist-action="open-module"]');
    });
  }
}
