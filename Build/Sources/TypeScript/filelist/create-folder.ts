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
import Modal from '@typo3/backend/modal';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/filelist/create-folder
 * @exports @typo3/filelist/create-folder
 */
class CreateFolder {
  private selfUrl: string;
  private confirmTitle: string;
  private confirmText: string;
  private changed: boolean = false;

  constructor() {
    DocumentService.ready().then((): void => {
      const mainElement: HTMLElement = document.querySelector('.filelist-create-folder-main');
      if (!(mainElement instanceof HTMLElement)) {
        throw new Error('Main element not found');
      }
      this.selfUrl = mainElement.dataset.selfUrl;
      this.confirmTitle = mainElement.dataset.confirmTitle;
      this.confirmText = mainElement.dataset.confirmText;
      this.registerEvents();
    });
  }

  public reload(amount: number): void {
    const url = this.selfUrl.replace(/AMOUNT/, amount.toString());
    if (!this.changed) {
      window.location.href = url;
    } else {
      const modal = Modal.confirm(this.confirmTitle, this.confirmText);
      modal.addEventListener('confirm.button.cancel', (): void => {
        modal.hideModal();
      });
      modal.addEventListener('confirm.button.ok', (): void => {
        modal.hideModal();
        window.location.href = url;
      });
    }
  }

  private registerEvents(): void {
    const inputElementSelectors = [
      'input[type="text"][name^="data[newfolder]"]',
      'input[type="text"][name^="data[newfile]"]',
      'input[type="text"][name^="data[newMedia]"]'
    ];
    new RegularEvent('change', (): void => {
      this.changed = true;
    }).delegateTo(document, inputElementSelectors.join(','));
    new RegularEvent('change', (e: Event): void => {
      const amount = parseInt((e.target as HTMLSelectElement).value, 10);
      this.reload(amount);
    }).bindTo(document.getElementById('number-of-new-folders'));
  }
}

export default new CreateFolder();
