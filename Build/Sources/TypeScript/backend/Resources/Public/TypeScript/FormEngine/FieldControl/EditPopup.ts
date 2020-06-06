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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');

/**
 * Handles the "Edit popup" field control that renders a new FormEngine instance
 */
class EditPopup {
  private controlElement: HTMLAnchorElement = null;
  private assignedFormField: HTMLSelectElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLAnchorElement>document.querySelector(controlElementId);
      this.assignedFormField = <HTMLSelectElement>document.querySelector(
        'select[data-formengine-input-name="' + this.controlElement.dataset.element + '"]',
      );

      if (this.assignedFormField.options.selectedIndex === -1) {
        this.controlElement.classList.add('disabled');
      }

      this.assignedFormField.addEventListener('change', this.registerChangeHandler);
      this.controlElement.addEventListener('click', this.registerClickHandler);
    });
  }

  private registerChangeHandler = (): void => {
    this.controlElement.classList.toggle('disabled', this.assignedFormField.options.selectedIndex === -1);
  }

  /**
   * @param {Event} e
   */
  private registerClickHandler = (e: Event): void => {
    e.preventDefault();

    const values: Array<string> = [];
    for (let i = 0; i < this.assignedFormField.selectedOptions.length; ++i) {
      const option  = this.assignedFormField.selectedOptions.item(i);
      values.push(option.value);
    }

    const url = this.controlElement.getAttribute('href')
      + '&P[currentValue]=' + encodeURIComponent(this.assignedFormField.value)
      + '&P[currentSelectedValues]=' + values.join(',')
    ;
    const popupWindow = window.open(url, '', this.controlElement.dataset.windowParameters);
    popupWindow.focus();
  }
}

export = EditPopup;
