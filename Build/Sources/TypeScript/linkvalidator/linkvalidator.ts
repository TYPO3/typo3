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

import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';

enum Selectors {
  actionButtonSelector = '.t3js-linkvalidator-action-button',
  toggleAllLinktypesSelector = '.t3js-linkvalidator-settings input[type="checkbox"].options-by-type-toggle-all',
  linktypesSelector = '.t3js-linkvalidator-settings input[type="checkbox"].options-by-type'
}

enum Identifier {
  toggleAllLinktypesId = 'options-by-type-toggle-all'
}

/**
 * Module: @typo3/linkvalidator/linkvalidator
 */
class Linkvalidator {
  constructor() {
    this.initializeEvents();
  }

  private static allCheckBoxesAreChecked(checkBoxes: NodeListOf<HTMLInputElement>): boolean {
    const checkboxArray = Array.from(checkBoxes);
    return checkBoxes.length === checkboxArray.filter((checkBox: HTMLInputElement) => checkBox.checked).length;
  }

  private toggleActionButton(): void {
    document.querySelector(Selectors.actionButtonSelector)?.toggleAttribute(
      'disabled',
      !document.querySelectorAll('input[type="checkbox"]:checked').length
    );
  }

  /**
   * Enables the "Toggle all" checkbox on document load if all child checkboxes are checked
   */
  private toggleTriggerCheckBox(): void {
    const checkBoxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(Selectors.linktypesSelector);
    (document.getElementById(Identifier.toggleAllLinktypesId) as HTMLInputElement).checked = Linkvalidator.allCheckBoxesAreChecked(checkBoxes);
  }

  private initializeEvents(): void {
    // toggleAll (checkboxes): on change
    new RegularEvent('change', (e: Event, currentTarget: HTMLInputElement): void => {
      const checkBoxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(Selectors.linktypesSelector);
      const checkIt = !Linkvalidator.allCheckBoxesAreChecked(checkBoxes);

      checkBoxes.forEach((checkBox: HTMLInputElement): void => {
        checkBox.checked = checkIt;
      });
      currentTarget.checked = checkIt;
      this.toggleActionButton();
    }).delegateTo(document, Selectors.toggleAllLinktypesSelector);

    // toggle (checkbox): on change
    new RegularEvent('change', (): void => {
      this.toggleTriggerCheckBox();
      this.toggleActionButton();
    }).delegateTo(document, Selectors.linktypesSelector);

    new RegularEvent('click', (e: PointerEvent, actionButton: HTMLInputElement): void => {
      Notification.success(actionButton.dataset.notificationMessage || 'Event triggered', '', 2);
    }).delegateTo(document, Selectors.actionButtonSelector);
  }
}

export default new Linkvalidator();
