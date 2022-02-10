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
import RegularEvent from '@typo3/core/event/regular-event';

class InputDateTimeElement {
  private element: HTMLInputElement = null;

  constructor(elementId: string) {
    DocumentService.ready().then((): void => {
      this.element = document.getElementById(elementId) as HTMLInputElement;
      this.registerEventHandler(this.element);
      import('../../date-time-picker').then(({default: DateTimePicker}): void => {
        DateTimePicker.initialize(this.element)
      });
    });
  }

  private registerEventHandler(element: HTMLInputElement): void {
    new RegularEvent('formengine.dp.change', (e: CustomEvent): void => {
      FormEngineValidation.validateField(e.target as HTMLInputElement);
      FormEngineValidation.markFieldAsChanged(e.target as HTMLInputElement);

      document.querySelectorAll('.module-docheader-bar .btn').forEach((btn: HTMLButtonElement): void => {
        btn.classList.remove('disabled');
        btn.disabled = false;
      });
    }).bindTo(element);
  }
}

export default InputDateTimeElement;
