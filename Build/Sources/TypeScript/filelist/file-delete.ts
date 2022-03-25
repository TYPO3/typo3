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

import {SeverityEnum} from '@typo3/backend/enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';
import Modal from '@typo3/backend/modal';

/**
 * Module: @typo3/filelist/file-delete
 * @exports @typo3/filelist/file-delete
 */
class FileDelete {
  constructor() {
    DocumentService.ready().then((): void => {
      new RegularEvent('click', (e: Event, eventTarget: HTMLElement): void => {
        e.preventDefault();
        let redirectUrl = eventTarget.dataset.redirectUrl;
        redirectUrl = (redirectUrl)
          ? encodeURIComponent(redirectUrl)
          : encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);

        const identifier = eventTarget.dataset.identifier;
        const deleteType = eventTarget.dataset.deleteType;
        const deleteUrl = eventTarget.dataset.deleteUrl + '&data[delete][0][data]=' + encodeURIComponent(identifier);
        const target = deleteUrl + '&data[delete][0][redirect]=' + redirectUrl;
        if (eventTarget.dataset.check) {
          const modal = Modal.confirm(eventTarget.dataset.title, eventTarget.dataset.bsContent, SeverityEnum.warning, [
            {
              text: TYPO3.lang['buttons.confirm.delete_file.no'] || 'Cancel',
              active: true,
              btnClass: 'btn-default',
              name: 'no',
            },
            {
              text: TYPO3.lang['buttons.confirm.' + deleteType + '.yes'] || 'Yes, delete this file or folder',
              btnClass: 'btn-warning',
              name: 'yes',
            },
          ]);
          modal.addEventListener('button.clicked', (evt: Event): void => {
            const element = evt.target as HTMLButtonElement;
            const name = element.name;
            if (name === 'no') {
              modal.hideModal();
            } else if (name === 'yes') {
              modal.hideModal();
              top.list_frame.location.href = target;
            }
          });
        } else {
          top.list_frame.location.href = target;
        }
      }).delegateTo(document, '.t3js-filelist-delete');
    });
  }
}

export default new FileDelete();
