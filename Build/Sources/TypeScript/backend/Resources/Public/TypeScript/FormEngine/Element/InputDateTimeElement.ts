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

class InputDateTimeElement {
  constructor(elementId: string) {
    $((): void => {
      this.registerEventHandler();
      require(['../../DateTimePicker'], (DateTimePicker: any): void => {
        DateTimePicker.initialize('#' + elementId)
      });
    });
  }

  private registerEventHandler(): void {
    $(document).on('formengine.dp.change', (event: JQueryEventObject, $field: JQuery): void => {
      FormEngine.Validation.validate();
      FormEngine.Validation.markFieldAsChanged($field);
      $('.module-docheader-bar .btn').removeClass('disabled').prop('disabled', false);
    });
  }
}

export = InputDateTimeElement;
