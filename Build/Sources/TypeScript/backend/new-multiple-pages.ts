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
import RegularEvent from '@typo3/core/event/regular-event';

enum Identifiers {
  containerSelector = '.t3js-newmultiplepages-container',
  addMoreFieldsButtonSelector = '.t3js-newmultiplepages-createnewfields',
  doktypeSelector = '.t3js-newmultiplepages-select-doktype',
  templateRow = '.t3js-newmultiplepages-newlinetemplate',
}

/**
 * Module: @typo3/backend/new-multiple-pages
 * JavaScript functions for creating multiple pages
 */
class NewMultiplePages {
  private lineCounter: number = 5;

  constructor() {
    DocumentService.ready().then((): void => {
      this.initializeEvents();
    });
  }

  /**
   * Register listeners
   */
  private initializeEvents(): void {
    new RegularEvent('click', this.createNewFormFields.bind(this))
      .delegateTo(document, Identifiers.addMoreFieldsButtonSelector);
    new RegularEvent('change', this.actOnTypeSelectChange)
      .delegateTo(document, Identifiers.doktypeSelector);
  }

  /**
   * Add further input rows
   */
  private createNewFormFields(): void {
    const multiplePagesContainer: HTMLDivElement = document.querySelector(Identifiers.containerSelector);
    const lineMarkup: string = document.querySelector(Identifiers.templateRow)?.innerHTML || '';
    if (multiplePagesContainer === null || lineMarkup === '') {
      return;
    }
    for (let i = 0; i < 5; i++) {
      const label = this.lineCounter + i + 1;
      multiplePagesContainer.innerHTML += lineMarkup
        .replace(/\[0\]/g, (this.lineCounter + i).toString())
        .replace(/\[1\]/g, label.toString());
    }
    this.lineCounter += 5;
  }

  private actOnTypeSelectChange(this: HTMLSelectElement): void {
    const optionElement: HTMLOptionElement = this.options[this.selectedIndex];
    const targetElement = document.querySelector(this.dataset.target);
    if (optionElement !== null && targetElement !== null) {
      targetElement.innerHTML = optionElement.dataset.icon;
    }
  }
}

export default new NewMultiplePages();
