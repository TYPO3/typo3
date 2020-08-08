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

import $ from 'jquery';

enum Identifiers {
  containerSelector = '.t3js-newmultiplepages-container',
  addMoreFieldsButtonSelector = '.t3js-newmultiplepages-createnewfields',
  doktypeSelector = '.t3js-newmultiplepages-select-doktype',
  templateRow = '.t3js-newmultiplepages-newlinetemplate',
}

/**
 * Module: TYPO3/CMS/Backend/NewMultiplePages
 * JavaScript functions for creating multiple pages
 */
class NewMultiplePages {
  private lineCounter: number = 5;

  constructor() {
    $((): void => {
      this.initializeEvents();
    });
  }

  /**
   * Register listeners
   */
  private initializeEvents(): void {
    $(Identifiers.addMoreFieldsButtonSelector).on('click', (): void => {
      this.createNewFormFields();
    });

    $(document).on('change', Identifiers.doktypeSelector, (e: JQueryEventObject): void => {
      this.actOnTypeSelectChange($(e.currentTarget));
    });
  }

  /**
   * Add further input rows
   */
  private createNewFormFields(): void {
    for (let i = 0; i < 5; i++) {
      const label = this.lineCounter + i + 1;
      const line = $(Identifiers.templateRow).html()
        .replace(/\[0\]/g, (this.lineCounter + i).toString())
        .replace(/\[1\]/g, label.toString());
      $(line).appendTo(Identifiers.containerSelector);
    }
    this.lineCounter += 5;
  }

  /**
   * @param {JQuery} $selectElement
   */
  private actOnTypeSelectChange($selectElement: JQuery): void {
    const $optionElement = $selectElement.find(':selected');
    const $target = $($selectElement.data('target'));
    $target.html($optionElement.data('icon'));
  }
}

export = new NewMultiplePages();
