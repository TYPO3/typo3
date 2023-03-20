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

import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';

/**
 * Module: @typo3/backend/multi-record-selection-action
 */

export interface ActionConfiguration {
  idField: string;
}

export interface ActionEventDetails {
  identifier: string,
  checkboxes: NodeListOf<HTMLInputElement>,
  configuration: any
}

export class MultiRecordSelectionAction {

  public static getEntityIdentifiers(eventDetails: ActionEventDetails): Array<string>
  {
    // Evaluate all checked records and if valid, add their uid to the list
    const entityIdentifiers: Array<string> = [];
    eventDetails.checkboxes.forEach((checkbox: HTMLInputElement): void => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null && checkboxContainer.dataset[eventDetails.configuration.idField]) {
        entityIdentifiers.push(checkboxContainer.dataset[eventDetails.configuration.idField]);
      }
    });
    return entityIdentifiers;
  }
}
