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
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';

interface PasswordRules {
  length: number;
  random: string;
  digitCharacters: boolean;
  lowerCaseCharacters: boolean;
  upperCaseCharacters: boolean;
  specialCharacters: boolean;
}

/**
 * Handles the "Generate Password" field control
 */
class PasswordGenerator {
  private controlElement: HTMLAnchorElement = null;
  private humanReadableField: HTMLInputElement = null;
  private hiddenField: HTMLInputElement = null;
  private passwordRules: PasswordRules = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLAnchorElement>document.getElementById(controlElementId);
      this.humanReadableField = <HTMLInputElement>document.querySelector(
        'input[data-formengine-input-name="' + this.controlElement.dataset.itemName + '"]',
      );
      this.hiddenField = <HTMLInputElement>document.querySelector(
        'input[name="' + this.controlElement.dataset.itemName + '"]',
      );
      this.passwordRules = JSON.parse(this.controlElement.dataset.passwordRules || '{}');

      // Set human-readable field to disable and readonly in case edit is disallowed in the field control settings
      if (!this.controlElement.dataset.allowEdit) {
        this.humanReadableField.disabled = true;
        this.humanReadableField.readOnly = true;
        // Also remove clearable possibility
        if (this.humanReadableField.isClearable || this.humanReadableField.classList.contains('t3js-clearable')) {
          this.humanReadableField.classList.remove('t3js-clearable');
          const clearableContainer = <HTMLDivElement>this.humanReadableField.closest('div.form-control-clearable-wrapper');
          if (clearableContainer) {
            clearableContainer.classList.remove('form-control-clearable');
            const closeButton = <HTMLButtonElement>clearableContainer.querySelector('button.close');
            if (closeButton) {
              clearableContainer.removeChild(closeButton);
            }
          }
        }
      }

      this.controlElement.addEventListener('click', this.generatePassword.bind(this));
    });
  }

  private generatePassword(e: Event): void {
    e.preventDefault();

    // Generate new password
    (new AjaxRequest(TYPO3.settings.ajaxUrls.password_generate)).post({
      passwordRules: this.passwordRules,
    })
      .then(async (response: AjaxResponse): Promise<void> => {
        const resolvedBody = await response.resolve();
        if (resolvedBody.success === true) {
          // Set type=text to display the generated password (allow to copy) and update the field value
          this.humanReadableField.type = 'text';
          this.humanReadableField.value = resolvedBody.password;
          // Manually dispatch "change" to enable FormEngine handling (instead of manually calling "updateInputField()").
          // This way custom modules are also triggered when listening on this event.
          this.humanReadableField.dispatchEvent(new Event('change'));
          // Due to formatting and processing done by FormEngine, we need to set the value again (allow to copy)
          this.humanReadableField.value = this.hiddenField.value;
          // Finally validate and mark the field as changed
          FormEngineValidation.validateField(this.humanReadableField);
          FormEngineValidation.markFieldAsChanged(this.humanReadableField);
        } else {
          Notification.warning(resolvedBody.message || 'No password was generated');
        }
      })
      .catch(() => {
        Notification.error('Password could not be generated');
      });
  }
}

export default PasswordGenerator;
