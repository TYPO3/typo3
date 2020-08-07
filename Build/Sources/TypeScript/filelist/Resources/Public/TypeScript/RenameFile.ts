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
 * Module: TYPO3/CMS/Filelist/RenameFile
 * Modal to pick the required conflict strategy for colliding filenames
 * @exports TYPO3/CMS/Filelist/RenameFile
 */
class RenameFile {

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    $('.t3js-submit-file-rename').on('click', this.checkForDuplicate);
  }

  private checkForDuplicate(e: any): void {
    e.preventDefault();

    const form: any = $('#' + $(e.currentTarget).attr('form'));
    const fileNameField: any = form.find('input[name="data[rename][0][target]"]');
    const conflictModeField: any = form.find('input[name="data[rename][0][conflictMode]"]');
    const ajaxUrl: string = TYPO3.settings.ajaxUrls.file_exists;

    $.ajax({
      cache: false,
      data: {
        fileName: fileNameField.val(),
        fileTarget: form.find('input[name="data[rename][0][destination]"]').val(),
      },
      success: (response: any): void => {
        const fileExists: boolean = typeof response.uid !== 'undefined';
        const originalFileName: string = fileNameField.data('original');
        const newFileName: string = fileNameField.val();

        if (fileExists && originalFileName !== newFileName) {
          const description: string = TYPO3.lang['file_rename.exists.description']
            .replace('{0}', originalFileName).replace(/\{1\}/g, newFileName);

          const modal: JQuery = Modal.confirm(
            TYPO3.lang['file_rename.exists.title'],
            description,
            SeverityEnum.warning,
            [
              {
                active: true,
                btnClass: 'btn-default',
                name: 'cancel',
                text: TYPO3.lang['file_rename.actions.cancel'],
              },
              {
                btnClass: 'btn-primary',
                name: 'rename',
                text: TYPO3.lang['file_rename.actions.rename'],
              },
              {
                btnClass: 'btn-default',
                name: 'replace',
                text: TYPO3.lang['file_rename.actions.override'],
              },
            ]);

          modal.on('button.clicked', (event: any): void => {
            if (event.target.name !== 'cancel') {
              conflictModeField.val(event.target.name);
              form.trigger('submit');
            }
            Modal.dismiss();
          });
        } else {
          form.trigger('submit');
        }
      },
      url: ajaxUrl,
    });
  }
}

export = new RenameFile();
