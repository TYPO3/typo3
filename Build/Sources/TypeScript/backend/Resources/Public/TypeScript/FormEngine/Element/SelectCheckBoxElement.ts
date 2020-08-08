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
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');

enum Identifier {
  toggleAll = '.t3js-toggle-checkboxes',
  singleItem = '.t3js-checkbox',
  revertSelection = '.t3js-revert-selection',
}

class SelectCheckBoxElement {
  private checkBoxId: string = '';
  private $table: JQuery = null;
  private checkedBoxes: JQuery = null;

  /**
   * Determines whether all available checkboxes are checked
   *
   * @param {JQuery} $checkBoxes
   * @return {boolean}
   */
  private static allCheckBoxesAreChecked($checkBoxes: JQuery): boolean {
    return $checkBoxes.length === $checkBoxes.filter(':checked').length;
  }

  /**
   * @param {string} checkBoxId
   */
  constructor(checkBoxId: string) {
    this.checkBoxId = checkBoxId;
    $((): void => {
      this.$table = $('#' + checkBoxId).closest('table');
      this.checkedBoxes = this.$table.find(Identifier.singleItem + ':checked');

      this.enableTriggerCheckBox();
      this.registerEventHandler();
    });
  }

  /**
   * Registers the events for clicking the "Toggle all" and the single item checkboxes
   */
  private registerEventHandler(): void {
    this.$table.on('change', Identifier.toggleAll, (e: JQueryEventObject): void => {
      const $me = $(e.currentTarget);
      const $checkBoxes = this.$table.find(Identifier.singleItem);
      const checkIt = !SelectCheckBoxElement.allCheckBoxesAreChecked($checkBoxes);

      $checkBoxes.prop('checked', checkIt);
      $me.prop('checked', checkIt);
      FormEngine.Validation.markFieldAsChanged($me);
    }).on('change', Identifier.singleItem, (): void => {
      this.setToggleAllState();
    }).on('click', Identifier.revertSelection, (): void => {
      this.$table.find(Identifier.singleItem).each((_: number, checkbox: HTMLInputElement): void => {
        checkbox.checked = this.checkedBoxes.index(checkbox) > -1;
      });
      this.setToggleAllState();
    });
  }

  private setToggleAllState(): void {
    const $checkBoxes = this.$table.find(Identifier.singleItem);
    const checkIt = SelectCheckBoxElement.allCheckBoxesAreChecked($checkBoxes);

    this.$table.find(Identifier.toggleAll).prop('checked', checkIt);
  }

  /**
   * Enables the "Toggle all" checkbox on document load if all child checkboxes are checked
   */
  private enableTriggerCheckBox(): void {
    const $checkBoxes = this.$table.find(Identifier.singleItem);
    const checkIt = SelectCheckBoxElement.allCheckBoxesAreChecked($checkBoxes);
    $('#' + this.checkBoxId).prop('checked', checkIt);
  }
}

export = SelectCheckBoxElement;
