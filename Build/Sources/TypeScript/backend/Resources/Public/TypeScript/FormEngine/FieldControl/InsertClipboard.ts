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

import DocumentService from 'TYPO3/CMS/Core/DocumentService';
import FormEngine from 'TYPO3/CMS/Backend/FormEngine';

interface ClipboardItem {
  title: string;
  value: string;
}

/**
 * Handles the "Insert clipboard" field control that pastes the clipboard into a "group" field
 */
class InsertClipboard {
  private controlElement: HTMLElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLElement>document.querySelector(controlElementId);
      this.controlElement.addEventListener('click', this.registerClickHandler);
    });
  }

  /**
   * @param {Event} e
   */
  private registerClickHandler = (e: Event): void => {
    e.preventDefault();

    const assignedElement: string = this.controlElement.dataset.element;
    const clipboardItems: Array<ClipboardItem> = JSON.parse(this.controlElement.dataset.clipboardItems);

    for (let item of clipboardItems) {
      FormEngine.setSelectOptionFromExternalSource(assignedElement, item.value, item.title, item.title);
    }
  }
}

export default InsertClipboard;
