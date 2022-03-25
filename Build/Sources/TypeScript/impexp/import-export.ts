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

import Modal from '@typo3/backend/modal';
import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/impexp/import-export
 * JavaScript to handle confirm windows in the Import/Export module
 * @exports @typo3/impexp/import-export
 */
class ImportExport {
  constructor() {
    DocumentService.ready().then((): void => this.registerEvents());
  }

  private registerEvents(): void {
    new RegularEvent('click', this.triggerConfirmation).delegateTo(document, '.t3js-confirm-trigger');

    const toggleDisabledControl = document.querySelector('.t3js-impexp-toggledisabled');
    if (toggleDisabledControl !== null) {
      new RegularEvent('click', this.toggleDisabled).bindTo(toggleDisabledControl);
    }
  }

  private triggerConfirmation(this: HTMLButtonElement): void {
    const modal = Modal.confirm(this.dataset.title, this.dataset.message);
    modal.addEventListener('confirm.button.ok', (): void => {
      const submitTrigger: HTMLInputElement = document.getElementById('t3js-submit-field') as HTMLInputElement;
      submitTrigger.name = this.name;
      submitTrigger.closest('form').submit();

      modal.hideModal();
    });
    modal.addEventListener('confirm.button.cancel', (): void => {
      modal.hideModal();
    });
  }

  private toggleDisabled() {
    const checkboxes: NodeListOf<HTMLInputElement> = document.querySelectorAll('table.t3js-impexp-preview tr[data-active="hidden"] input.t3js-exclude-checkbox');
    if (checkboxes.length > 0) {
      const firstCheckbox = checkboxes.item(0);
      checkboxes.forEach((element: HTMLInputElement): void => {
        element.checked = !firstCheckbox.checked;
      });
    }
  }
}

export default new ImportExport();
