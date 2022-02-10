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

enum Identifier {
  toggleAll = '.t3js-toggle-checkboxes',
  singleItem = '.t3js-checkbox',
  revertSelection = '.t3js-revert-selection',
}

class SelectCheckBoxElement {
  private checkBoxId: string = '';
  private table: HTMLTableElement = null;
  private checkedBoxes: NodeListOf<HTMLInputElement> = null;

  /**
   * Determines whether all available checkboxes are checked
   *
   * @param {NodeListOf<HTMLInputElement>} checkBoxes
   * @return {boolean}
   */
  private static allCheckBoxesAreChecked(checkBoxes: NodeListOf<HTMLInputElement>): boolean {
    const checkboxArray = Array.from(checkBoxes);
    return checkBoxes.length === checkboxArray.filter((checkBox: HTMLInputElement) => checkBox.checked).length;
  }

  /**
   * @param {string} checkBoxId
   */
  constructor(checkBoxId: string) {
    this.checkBoxId = checkBoxId;
    DocumentService.ready().then((document: Document): void => {
      this.table = document.getElementById(checkBoxId).closest('table');
      this.checkedBoxes = this.table.querySelectorAll(Identifier.singleItem + ':checked');

      this.enableTriggerCheckBox();
      this.registerEventHandler();
    });
  }

  /**
   * Registers the events for clicking the "Toggle all" and the single item checkboxes
   */
  private registerEventHandler(): void {
    new RegularEvent('change', (e: Event, currentTarget: HTMLInputElement): void => {
      const checkBoxes: NodeListOf<HTMLInputElement> = this.table.querySelectorAll(Identifier.singleItem);
      const checkIt = !SelectCheckBoxElement.allCheckBoxesAreChecked(checkBoxes);

      checkBoxes.forEach((checkBox: HTMLInputElement): void => {
        checkBox.checked = checkIt;
      });
      currentTarget.checked = checkIt;
    }).delegateTo(this.table, Identifier.toggleAll);

    new RegularEvent('change', this.setToggleAllState.bind(this)).delegateTo(this.table, Identifier.singleItem);

    new RegularEvent('click', (): void => {
      const checkBoxes = this.table.querySelectorAll(Identifier.singleItem);
      const checkedCheckBoxesAsArray = Array.from(this.checkedBoxes);
      checkBoxes.forEach((checkBox: HTMLInputElement): void => {
        checkBox.checked = checkedCheckBoxesAsArray.includes(checkBox);
      });
      this.setToggleAllState();
    }).delegateTo(this.table, Identifier.revertSelection);
  }

  private setToggleAllState(): void {
    const checkBoxes: NodeListOf<HTMLInputElement> = this.table.querySelectorAll(Identifier.singleItem);
    (this.table.querySelector(Identifier.toggleAll) as HTMLInputElement).checked = SelectCheckBoxElement.allCheckBoxesAreChecked(checkBoxes);
  }

  /**
   * Enables the "Toggle all" checkbox on document load if all child checkboxes are checked
   */
  private enableTriggerCheckBox(): void {
    const checkBoxes: NodeListOf<HTMLInputElement> = this.table.querySelectorAll(Identifier.singleItem);
    (document.getElementById(this.checkBoxId) as HTMLInputElement).checked = SelectCheckBoxElement.allCheckBoxesAreChecked(checkBoxes);
  }
}

export default SelectCheckBoxElement;
