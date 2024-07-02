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
import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';
import SortableTable from '@typo3/backend/sortable-table';

enum Selectors {
  // Check
  linktypesSelectorCheck = '.t3js-linkvalidator-settings input[type="checkbox"].options-by-type-check',
  actionButtonSelectorCheck = '.t3js-linkvalidator-action-button-check',

  // Report
  toggleAllLinktypesSelectorReport = '.t3js-linkvalidator-settings input[type="checkbox"].options-by-type-toggle-all-report',
  linktypesSelectorReport = '.t3js-linkvalidator-settings input[type="checkbox"].options-by-type-report',
  actionButtonSelectorReport = '.t3js-linkvalidator-action-button-report'
}

enum Identifier {
  toggleAllLinktypesIdReport = 'options-by-type-toggle-all-report',
  brokenLinksTableIdReport = 'typo3-broken-links-table'
}

/**
 * Module: @typo3/linkvalidator/linkvalidator
 */
class Linkvalidator {
  constructor() {
    DocumentService.ready().then((): void => {
      const linkList = document.getElementById(Identifier.brokenLinksTableIdReport);
      if (linkList !== null) {
        if (linkList instanceof HTMLTableElement) {
          new SortableTable(linkList);
        }
      }
    });

    this.initializeEvents();
  }

  private static allCheckBoxesAreChecked(checkBoxes: NodeListOf<HTMLInputElement>): boolean {
    const checkboxArray = Array.from(checkBoxes);
    return checkBoxes.length === checkboxArray.filter((checkBox: HTMLInputElement) => checkBox.checked).length;
  }

  private toggleActionButtonReport(): void {
    document.querySelector(Selectors.actionButtonSelectorReport)?.toggleAttribute(
      'disabled',
      !document.querySelectorAll('input[type="checkbox"]:checked').length
    );
  }

  /**
   * Enables the "Toggle all" checkbox on document load if all child checkboxes are checked
   */
  private toggleTriggerCheckBoxReport(): void {
    const checkBoxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(Selectors.linktypesSelectorReport);
    (document.getElementById(Identifier.toggleAllLinktypesIdReport) as HTMLInputElement).checked = Linkvalidator.allCheckBoxesAreChecked(checkBoxes);
  }

  private initializeEvents(): void {
    // toggleAll (checkboxes): on change
    new RegularEvent('change', (e: Event, currentTarget: HTMLInputElement): void => {
      const checkBoxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(Selectors.linktypesSelectorReport);
      const checkIt = !Linkvalidator.allCheckBoxesAreChecked(checkBoxes);

      checkBoxes.forEach((checkBox: HTMLInputElement): void => {
        checkBox.checked = checkIt;
      });
      currentTarget.checked = checkIt;
      this.toggleActionButtonReport();
    }).delegateTo(document, Selectors.toggleAllLinktypesSelectorReport);

    // toggle (checkbox): on change
    new RegularEvent('change', (): void => {
      this.toggleTriggerCheckBoxReport();
      this.toggleActionButtonReport();
    }).delegateTo(document, Selectors.linktypesSelectorReport);

    new RegularEvent('click', (e: PointerEvent, actionButton: HTMLInputElement): void => {
      Notification.success(actionButton.dataset.notificationMessage || 'Event triggered', '', 2);
    }).delegateTo(document, Selectors.actionButtonSelectorCheck);

    new RegularEvent('click', (e: PointerEvent, actionButton: HTMLInputElement): void => {
      Notification.success(actionButton.dataset.notificationMessage || 'Event triggered', '', 2);
    }).delegateTo(document, Selectors.actionButtonSelectorReport);
  }
}

export default new Linkvalidator();
