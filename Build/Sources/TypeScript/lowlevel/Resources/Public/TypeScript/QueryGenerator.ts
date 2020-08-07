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
import 'TYPO3/CMS/Backend/Input/Clearable';

/**
 * Module: TYPO3/CMS/Lowlevel/QueryGenerator
 * This module handle the QueryGenerator forms.
 */
class QueryGenerator {
  private form: JQuery = null;
  private limitField: JQuery = null;

  constructor() {
    this.initialize();
  }

  /**
   * Initialize the QueryGenerator object
   */
  private initialize(): void {
    this.form = $('form[name="queryform"]');
    this.limitField = $('#queryLimit');
    this.form.on('click', '.t3js-submit-click', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.doSubmit();
    });
    this.form.on('change', '.t3js-submit-change', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.doSubmit();
    });
    this.form.on('click', '.t3js-limit-submit input[type="button"]', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.setLimit($(e.currentTarget).data('value'));
      this.doSubmit();
    });
    this.form.on('click', '.t3js-addfield', (e: JQueryEventObject): void => {
      e.preventDefault();
      const $field = $(e.currentTarget);
      this.addValueToField($field.data('field'), $field.val());
    });
    this.form.on('change', '[data-assign-store-control-title]', (evt: JQueryEventObject): void => {
      const $currentTarget = $(evt.currentTarget);
      const $titleField = this.form.find('[name="storeControl\[title\]"]');
      if ($currentTarget.val() !== '0') {
        $titleField.val($currentTarget.find('option:selected').text());
      } else {
        $titleField.val('');
      }
    });
    (<NodeListOf<HTMLInputElement>>document.querySelectorAll('form[name="queryform"] .t3js-clearable')).forEach(
      (clearableField: HTMLInputElement) => clearableField.clearable({
        onClear: (): void => {
          this.doSubmit();
        },
      }),
    );
  }

  /**
   * Submit the form
   */
  private doSubmit(): void {
    this.form.trigger('submit');
  }

  /**
   * Set query limit
   *
   * @param {String} value
   */
  private setLimit(value: string): void {
    this.limitField.val(value);
  }

  /**
   * Add value to text field
   *
   * @param {String} field the name of the field
   * @param {String} value the value to add
   */
  private addValueToField(field: string, value: string): void {
    const $target = this.form.find('[name="' + field + '"]');
    const currentValue = $target.val();
    $target.val(currentValue + ',' + value);
  }
}

export = new QueryGenerator();
