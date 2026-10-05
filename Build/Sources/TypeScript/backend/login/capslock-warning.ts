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

import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/backend/login/capslock-warning
 * Shows a warning next to fields marked with `t3js-capslockwarning` while capslock is active
 * @exports @typo3/backend/login/capslock-warning
 */
class CapslockWarning {
  constructor() {
    DocumentService.ready().then((): void => {
      document.querySelectorAll<HTMLInputElement>('.t3js-capslockwarning').forEach((field: HTMLInputElement): void => this.bindTo(field));
    });
  }

  public bindTo(field: HTMLInputElement): void {
    ['keydown', 'keyup'].forEach((eventName: string) => new RegularEvent(eventName, this.updateWarning).bindTo(field));
  }

  private readonly updateWarning = (event: Event): void => {
    // Browser autofill dispatches plain events without modifier state
    if (!(event instanceof KeyboardEvent)) {
      return;
    }
    const field = event.target as HTMLInputElement;
    if (event.getModifierState('CapsLock')) {
      this.attachWarning(field);
    } else {
      this.removeWarning(field);
    }
  };

  private attachWarning(field: HTMLInputElement): void {
    const targetContainer = field.closest('.input-group');
    if (!targetContainer || targetContainer.querySelector('.input-group-text-warning-capslock')) {
      return;
    }

    const warningIcon = `
      <span class="icon icon-size-small icon-state-default">
          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="m8 5.414 3.536 3.536.707-.707L8 4 3.757 8.243l.707.707L8 5.414zM4 11h8v1H4z"/></g></svg>
          </span>
      </span>
    `;

    const warningMessage = document.createElement('span');
    warningMessage.classList.add('visually-hidden');
    warningMessage.textContent = field.dataset.capslockwarningMessage ?? '';

    const warningElement = document.createElement('span');
    warningElement.classList.add('input-group-text', 'input-group-text-warning', 'input-group-text-warning-capslock');
    warningElement.role = 'status';
    warningElement.innerHTML = warningIcon;
    warningElement.title = field.dataset.capslockwarningTitle ?? '';
    warningElement.appendChild(warningMessage);

    const parentElement = field.parentElement;
    if (parentElement.classList.contains('form-control-clearable-wrapper')) {
      parentElement.insertAdjacentElement('afterend', warningElement);
      return;
    }
    field.insertAdjacentElement('afterend', warningElement);
  }

  private removeWarning(field: HTMLInputElement): void {
    field.closest('.input-group')?.querySelector('.input-group-text-warning-capslock')?.remove();
  }
}

export default new CapslockWarning();
