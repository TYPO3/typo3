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

/// <amd-dependency path='TYPO3/CMS/Backend/Modal' name='Modal'>
/// <amd-dependency path='TYPO3/CMS/Backend/Severity' name='Severity'>

import $ = require('jquery');
declare const Modal: any;
declare const Severity: any;
declare const TYPO3: any;

/**
 * Module: TYPO3/CMS/Backend/RenameFile
 * Modal to pick the required conflict strategy for colliding filenames
 * @exports TYPO3/CMS/Backend/RenameFile
 */
class RenameFile {

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    (<any> $('.t3js-submit-file-rename')).on('click', this.checkForDuplicate);
  }

  private checkForDuplicate(e: any): void {
    e.preventDefault();

    const form: any = $(e.currentTarget).closest('form');
    const fileNameField: any = form.find('input[name="file[rename][0][target]"]');
    const conflictModeField: any = form.find('input[name="file[rename][0][conflictMode]"]');
    const ajaxUrl: string = TYPO3.settings.ajaxUrls.file_exists;

    $.ajax({
      cache: false,
      data: {
        fileName: fileNameField.val(),
        fileTarget: form.find('input[name="file[rename][0][destination]"]').val(),
      },
      success: function (response: any): void {
        const fileExists: boolean = response !== false;
        const originalFileName: string = fileNameField.data('original');
        const newFileName: string = fileNameField.val();

        if (fileExists && originalFileName !== newFileName) {
          const description: string = TYPO3.lang['file_rename.exists.description']
            .replace('{0}', originalFileName).replace('{1}', newFileName);

          const modal: boolean = Modal.confirm(
            TYPO3.lang['file_rename.exists.title'],
            description,
            Severity.warning,
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

          (<any> modal).on('button.clicked', function (event: any): void {
            conflictModeField.val(event.target.name);
            form.submit();
            Modal.dismiss();
          });
        } else {
          form.submit();
        }
      },
      url: ajaxUrl,
    });
  };
}

export = new RenameFile();
