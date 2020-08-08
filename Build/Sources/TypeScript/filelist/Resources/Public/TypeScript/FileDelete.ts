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

import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import $ from 'jquery';
import Modal = require('TYPO3/CMS/Backend/Modal');

/**
 * Module: TYPO3/CMS/Filelist/FileDelete
 * @exports TYPO3/CMS/Filelist/FileDelete
 */
class FileDelete {
  constructor() {
    $((): void => {
      $(document).on('click', '.t3js-filelist-delete', (e: JQueryEventObject): void => {
        e.preventDefault();
        const $anchorElement = $(e.currentTarget);
        let redirectUrl = $anchorElement.data('redirectUrl');
        redirectUrl = (redirectUrl)
          ? encodeURIComponent(redirectUrl)
          : encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);

        const identifier = $anchorElement.data('identifier');
        const deleteType = $anchorElement.data('deleteType');
        const deleteUrl = $anchorElement.data('deleteUrl') + '&data[delete][0][data]=' + encodeURIComponent(identifier);
        const target = deleteUrl + '&data[delete][0][redirect]=' + redirectUrl;
        if ($anchorElement.data('check')) {
          const $modal = Modal.confirm($anchorElement.data('title'), $anchorElement.data('content'), SeverityEnum.warning, [
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
          $modal.on('button.clicked', (evt: JQueryEventObject): void => {
            const $element = <HTMLInputElement>evt.target;
            const name = $element.name;
            if (name === 'no') {
              Modal.dismiss();
            } else if (name === 'yes') {
              Modal.dismiss();
              top.list_frame.location.href = target;
            }
          });
        } else {
          top.list_frame.location.href = target;
        }
      });
    });
  }
}

export = new FileDelete();
