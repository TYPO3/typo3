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

/**
 * DatePicker initialization used by DatePickerViewHelper.
 *
 * Note: Requires jQuery UI to be included on the page.
 *
 * Scope: frontend
 */
interface JQuery { // eslint-disable-line @typescript-eslint/no-unused-vars
  datepicker(optionsOrInstruction: object | string, value?: string): JQuery;
}
if (typeof $ !== 'undefined') { // eslint-disable-line no-restricted-globals
  $(function(jQuery: JQueryStatic) { // eslint-disable-line no-restricted-globals
    jQuery('input[data-t3-form-datepicker]').each(function (this: HTMLInputElement) {
      jQuery(this).datepicker({
        dateFormat: jQuery(this).data('format')
      }).on('keydown', function(this: HTMLInputElement, e: JQueryEventObject) {
        // By using "backspace" or "delete", you can clear the datepicker again.
        if (e.keyCode === 8 || e.keyCode === 46) {
          e.preventDefault();
          jQuery(this).datepicker('setDate', '');
        }
      });
    });
  });
}
