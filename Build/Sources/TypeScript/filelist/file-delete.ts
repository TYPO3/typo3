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

import { SeverityEnum } from '@typo3/backend/enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';
import Modal from '@typo3/backend/modal';
import labels from '~labels/backend.alt_doc';

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

        const identifier = eventTarget.dataset.filelistDeleteIdentifier;
        const deleteType = eventTarget.dataset.filelistDeleteType;
        const deleteUrl = eventTarget.dataset.filelistDeleteUrl + '&data[delete][0][data]=' + encodeURIComponent(identifier);
        const target = deleteUrl + '&data[delete][0][redirect]=' + redirectUrl;
        if (eventTarget.dataset.filelistDeleteCheck) {
          const modal = Modal.confirm(eventTarget.dataset.title, eventTarget.dataset.content, SeverityEnum.warning, [
            {
              text: labels.get('buttons.confirm.delete_file.no'),
              active: true,
              btnClass: 'btn-default',
              name: 'no',
            },
            {
              text: deleteType === 'delete_folder' ? labels.get('buttons.confirm.delete_folder.yes') : labels.get('buttons.confirm.delete_file.yes'),
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
      }).delegateTo(document, '[data-filelist-delete="true"]');
    });
  }
}

export default new FileDelete();
