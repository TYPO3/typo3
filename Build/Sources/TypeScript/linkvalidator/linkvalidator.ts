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
  settingsContainerSelector = '.t3js-linkvalidator-settings',
  actionButtonSelector = '.t3js-linkvalidator-action-button'
}

/**
 * Module: @typo3/linkvalidator/linkvalidator
 */
class Linkvalidator {
  private static toggleActionButtons(settingsContainer: HTMLElement): void {
    settingsContainer.querySelector(Selectors.actionButtonSelector)?.toggleAttribute(
      'disabled',
      !settingsContainer.querySelectorAll('input[type="checkbox"]:checked').length
    );
  }

  constructor() {
    this.initializeEvents();
    document.querySelectorAll(Selectors.settingsContainerSelector).forEach((container: HTMLElement): void => {
      Linkvalidator.toggleActionButtons(container);
    })
  }

  private initializeEvents(): void {
    new RegularEvent('change', (e: Event, checkbox: HTMLInputElement): void => {
      Linkvalidator.toggleActionButtons(checkbox.closest(Selectors.settingsContainerSelector));
    }).delegateTo(document, [Selectors.settingsContainerSelector, 'input[type="checkbox"]'].join(' '));

    new RegularEvent('click', (e: PointerEvent, actionButton: HTMLInputElement): void => {
      Notification.success(actionButton.dataset.notificationMessage || 'Event triggered', '', 2);
    }).delegateTo(document, Selectors.actionButtonSelector);
  }
}

export default new Linkvalidator();
