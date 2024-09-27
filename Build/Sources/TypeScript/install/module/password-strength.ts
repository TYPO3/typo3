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

class PasswordStrength {
  public initialize(fieldElement: HTMLInputElement): void {
    // Simple password strength indicator
    new RegularEvent('keyup', (event: Event): void => {
      const field = event.target as HTMLInputElement;
      this.checkPassword(field);
    }).bindTo(fieldElement);

    new RegularEvent('blur', (event: Event): void => {
      const field = event.target as HTMLInputElement;
      field.classList.remove('has-error', 'has-success', 'has-warning');
    }).bindTo(fieldElement);

    new RegularEvent('focus', (event: Event): void => {
      const field = event.target as HTMLInputElement;
      this.checkPassword(field);
    }).bindTo(fieldElement);
  }

  private checkPassword(fieldElement: HTMLInputElement): void {
    const value = fieldElement.value;
    const strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
    const mediumRegex = new RegExp('^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
    const enoughRegex = new RegExp('(?=.{8,}).*', 'g');
    fieldElement.classList.remove('has-error', 'has-success', 'has-warning');

    if (value.length === 0) {
      fieldElement.classList.add('has-error');
    } else if (!enoughRegex.test(value)) {
      fieldElement.classList.add('has-error');
    } else if (strongRegex.test(value)) {
      fieldElement.classList.add('has-success');
    } else if (mediumRegex.test(value)) {
      fieldElement.classList.add('has-warning');
    } else {
      fieldElement.classList.add('has-warning');
    }
  }
}

export default new PasswordStrength();
