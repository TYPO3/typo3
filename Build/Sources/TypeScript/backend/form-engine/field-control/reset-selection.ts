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

/**
 * This module is used for the field control "Reset selection" used for selectSingleBox
 */
class ResetSelection {
  private controlElement: HTMLElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLElement>document.querySelector(controlElementId);
      if (this.controlElement !== null) {
        this.controlElement.addEventListener('click', this.registerClickHandler);
      }
    });
  }

  /**
   * @param {Event} e
   */
  private registerClickHandler = (e: Event): void => {
    e.preventDefault();

    const itemName: string = this.controlElement.dataset.itemName;
    const selectedIndices: Array<number> = JSON.parse(this.controlElement.dataset.selectedIndices);
    const field = (<HTMLSelectElement>document.forms.namedItem('editform').querySelector('[name="' + itemName + '[]"]'));

    field.selectedIndex = -1;
    for (let i of selectedIndices) {
      field.options[i].selected = true;
    }
  }
}

export default ResetSelection;
