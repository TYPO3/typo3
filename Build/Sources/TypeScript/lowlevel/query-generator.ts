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

import '@typo3/backend/input/clearable';
import DateTimePicker from '@typo3/backend/date-time-picker';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/lowlevel/query-generator
 * This module handle the QueryGenerator forms.
 */
class QueryGenerator {
  private readonly form: HTMLFormElement = document.querySelector('form[name="queryform"]');
  private readonly searchField: HTMLInputElement = document.querySelector('input#searchField');
  private readonly submitSearch: HTMLInputElement = document.querySelector('button#submitSearch');
  private readonly activeSearch: boolean = this.searchField ? (this.searchField.value !== '') : false;
  private readonly limitField: HTMLInputElement = document.querySelector('input#queryLimit');

  constructor() {
    if (this.submitSearch && this.activeSearch) {
      this.submitSearch.removeAttribute('disabled');
    }

    if (this.searchField) {
      new RegularEvent('search', (): void => {
        if (this.searchField.value === '' && this.activeSearch) {
          this.doSubmit();
        }
      }).bindTo(this.searchField);

      new RegularEvent('input', (): void => {
        if (this.searchField.value === '' && this.activeSearch) {
          this.doSubmit();
        }
        this.submitSearch.toggleAttribute('disabled', this.searchField.value === '');
      }).bindTo(this.searchField);

      new RegularEvent('submit', (event: Event): void => {
        if (this.searchField.value === '' && !this.activeSearch) {
          event.preventDefault();
        }
      }).bindTo(this.form);
    }

    new RegularEvent('click', (event: Event) => {
      event.preventDefault();
      this.doSubmit();
    }).delegateTo(this.form, '.t3js-submit-click');

    new RegularEvent('change', (event: Event) => {
      event.preventDefault();
      this.doSubmit();
    }).delegateTo(this.form, '.t3js-submit-change');

    new RegularEvent('click', (event: Event, element: HTMLButtonElement) => {
      event.preventDefault();
      this.setLimit(element.value);
      this.doSubmit();
    }).delegateTo(this.form, '.t3js-limit-submit input[type="button"]');

    new RegularEvent('click', (event: Event, element: HTMLButtonElement) => {
      event.preventDefault();
      this.addValueToField(element.dataset.field, element.value);
    }).delegateTo(this.form, '.t3js-addfield');

    new RegularEvent('change', (event: Event, element: HTMLSelectElement) => {
      const titleField = <HTMLInputElement>this.form.querySelector('input[name="storeControl[title]"]');
      if (element.value !== '0') {
        titleField.value = element.querySelector('option:selected').textContent;
      } else {
        titleField.value = '';
      }
    }).delegateTo(this.form, 'select.t3js-addfield');

    (<NodeListOf<HTMLInputElement>>document.querySelectorAll('form[name="queryform"] .t3js-clearable')).forEach(
      (clearableField: HTMLInputElement) => clearableField.clearable({
        onClear: (): void => {
          this.doSubmit();
        },
      }),
    );
    (<NodeListOf<HTMLInputElement>>document.querySelectorAll('form[name="queryform"] .t3js-datetimepicker')).forEach(
      (dateTimePickerElement: HTMLInputElement) => DateTimePicker.initialize(dateTimePickerElement)
    );
  }

  /**
   * Submit the form
   */
  private doSubmit(): void {
    this.form.submit();
  }

  /**
   * Set query limit
   *
   * @param {String} value
   */
  private setLimit(value: string): void {
    this.limitField.value = value;
  }

  /**
   * Add value to text field
   *
   * @param {String} field the name of the field
   * @param {String} value the value to add
   */
  private addValueToField(field: string, value: string): void {
    const target = <HTMLInputElement>this.form.querySelector('[name="' + field + '"]');
    value = target.value + ',' + value;
    target.value = value
      .split(',')
      // Remove whitespace from fields
      .map(fieldName => fieldName.trim())
      // Ensure fields only exist once
      .filter((value, index, array) => array.indexOf(value) === index)
      .join(',');
  }
}

export default new QueryGenerator();
