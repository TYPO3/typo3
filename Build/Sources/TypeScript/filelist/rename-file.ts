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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Modal from '@typo3/backend/modal';
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/filelist/rename-file
 * Modal to pick the required conflict strategy for colliding filenames
 * @exports @typo3/filelist/rename-file
 */
class RenameFile {

  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  public initialize(): void {
    const submitButton = document.querySelector('.t3js-submit-file-rename');
    if (submitButton !== null) {
      submitButton.addEventListener('click', this.checkForDuplicate)
    }
  }

  private checkForDuplicate(e: Event): void {
    e.preventDefault();

    const form: HTMLFormElement = (e.currentTarget as HTMLInputElement).form;
    const fileNameField = form.querySelector('input[name="data[rename][0][target]"]') as HTMLInputElement;
    const destinationField = form.querySelector('input[name="data[rename][0][destination]"]') as HTMLInputElement;
    const conflictModeField = form.querySelector('input[name="data[rename][0][conflictMode]"]') as HTMLInputElement;

    const data: any = {
      fileName: fileNameField.value
    };
    // destination is not set if we deal with a folder
    if (destinationField !== null) {
      data.fileTarget = destinationField.value;
    }

    new AjaxRequest(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments(data).get({cache: 'no-cache'}).then(async (response: AjaxResponse): Promise<void> => {
      const result = await response.resolve();

      const fileExists: boolean = typeof result.uid !== 'undefined';
      const originalFileName: string = fileNameField.dataset.original;
      const newFileName: string = fileNameField.value;

      if (fileExists && originalFileName !== newFileName) {
        const description: string = TYPO3.lang['file_rename.exists.description']
          .replace('{0}', originalFileName).replace(/\{1\}/g, newFileName);

        const modal = Modal.confirm(
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
            // conflictMode is not set if we deal with a folder
            if (conflictModeField !== null) {
              conflictModeField.value = event.target.name;
            }
            form.submit();
          }
          Modal.dismiss();
        });
      } else {
        form.submit();
      }
    });
  }
}

export default new RenameFile();
