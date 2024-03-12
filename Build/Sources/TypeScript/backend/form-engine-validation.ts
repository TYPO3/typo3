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
 * Module: @typo3/backend/form-engine-validation
 * Contains all JS functions related to TYPO3 TCEforms/FormEngineValidation
 * @internal
 */
import $ from 'jquery';
import { DateTime } from 'luxon';
import Md5 from '@typo3/backend/hashing/md5';
import DocumentSaveActions from '@typo3/backend/document-save-actions';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import Utility from './utility';
import RegularEvent from '@typo3/core/event/regular-event';
import DomHelper from '@typo3/backend/utility/dom-helper';
import { selector } from '@typo3/core/literals';

type FormEngineFieldElement = HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement;
type CustomEvaluationCallback = (value: string) => string;

export default (function() {

  /**
   * The main FormEngineValidation object
   *
   * @type {{rulesSelector: string, inputSelector: string, markerSelector: string, groupFieldHiddenElement: string, relatedFieldSelector: string, errorClass: string, lastYear: number, lastDate: number, lastTime: number, passwordDummy: string}}
   * @exports @typo3/backend/form-engine-validation
   */
  const FormEngineValidation: any = {
    rulesSelector: '[data-formengine-validation-rules]',
    inputSelector: '[data-formengine-input-params]',
    markerSelector: '.t3js-formengine-validation-marker',
    groupFieldHiddenElement: '.t3js-formengine-field-group input[type=hidden]',
    relatedFieldSelector: '[data-relatedfieldname]',
    errorClass: 'has-error',
    lastYear: 0,
    lastDate: 0,
    lastTime: 0,
    passwordDummy: '********'
  };

  let formEngineFormElement: HTMLFormElement;

  const customEvaluations: Map<string, CustomEvaluationCallback> = new Map();

  /**
   * Initialize validation for the first time
   */
  FormEngineValidation.initialize = function(formElement: HTMLFormElement): void {
    formEngineFormElement = formElement;
    formEngineFormElement.querySelectorAll('.' + FormEngineValidation.errorClass).forEach((e: HTMLElement) => e.classList.remove(FormEngineValidation.errorClass));

    // Initialize input fields
    FormEngineValidation.initializeInputFields();

    // Bind to field changes
    new RegularEvent('change', (e: Event, target: FormEngineFieldElement): void => {
      FormEngineValidation.validateField(target);
      FormEngineValidation.markFieldAsChanged(target);
    }).delegateTo(formEngineFormElement, FormEngineValidation.rulesSelector);

    FormEngineValidation.registerSubmitCallback();

    const today = new Date();
    FormEngineValidation.lastYear = FormEngineValidation.getYear(today);
    FormEngineValidation.lastDate = FormEngineValidation.getDate(today);
    FormEngineValidation.lastTime = 0;
    FormEngineValidation.validate();
  };

  /**
   * Initialize all input fields
   */
  FormEngineValidation.initializeInputFields = function(): void {
    formEngineFormElement.querySelectorAll(FormEngineValidation.inputSelector).forEach((visibleField: FormEngineFieldElement): void => {
      const config = JSON.parse(visibleField.dataset.formengineInputParams);
      const fieldName = config.field;
      const actualValueField = formEngineFormElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;

      // ignore fields which already have been initialized
      if (!('formengineInputInitialized' in visibleField.dataset)) {
        actualValueField.dataset.config = visibleField.dataset.formengineInputParams;
        FormEngineValidation.initializeInputField(fieldName);
      }
    });
  };

  /**
   * Initialize field by name
   */
  FormEngineValidation.initializeInputField = function(fieldName: string): void {
    const field = formEngineFormElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;
    const humanReadableField = formEngineFormElement.querySelector(selector`[data-formengine-input-name="${fieldName}"]`) as FormEngineFieldElement;

    if (field.dataset.config !== undefined) {
      const config = JSON.parse(field.dataset.config);
      const evalList = Utility.trimExplode(',', config.evalList);
      let value = field.value;

      for (let i = 0; i < evalList.length; i++) {
        value = FormEngineValidation.formatValue(evalList[i], value, config);
      }
      if (value.length) {
        humanReadableField.value = value;
      }
    }

    new RegularEvent('change', (): void => {
      FormEngineValidation.updateInputField(humanReadableField.dataset.formengineInputName);
    }).bindTo(humanReadableField);

    // add the attribute so that acceptance tests can know when the field initialization has completed
    humanReadableField.dataset.formengineInputInitialized = 'true';
  };

  FormEngineValidation.registerCustomEvaluation = function(name: string, handler: CustomEvaluationCallback): void {
    if (!customEvaluations.has(name)) {
      customEvaluations.set(name, handler);
    }
  };

  /**
   * Format field value
   */
  FormEngineValidation.formatValue = function(type: string, value: string|number, config: object): string {
    let theString = '';
    let parsedInt: number;
    let theTime: Date;
    switch (type) {
      case 'date':
        // poor man’s ISO-8601 detection: if we have a "-" in it, it apparently is not an integer.
        if (value.toString().indexOf('-') > 0) {
          const date = DateTime.fromISO(value.toString(), { zone: 'utc' });
          theString = date.toFormat('dd-MM-yyyy');
        } else {
          if (value === '' || value === '0') {
            return '';
          }
          parsedInt = parseInt(value.toString(), 10);
          if (isNaN(parsedInt)) {
            return '';
          }
          theTime = new Date(parsedInt * 1000);
          const day = (theTime.getUTCDate()).toString(10).padStart(2, '0');
          const month = (theTime.getUTCMonth() + 1).toString(10).padStart(2, '0');
          const year = this.getYear(theTime);
          theString = day + '-' + month + '-' + year;
        }
        break;
      case 'datetime':
        if (value === '' || value === '0') {
          // if value is '0' (string), it's supposed to be empty
          return '';
        }
        theString = (FormEngineValidation.formatValue('time', value, config) + ' ' + FormEngineValidation.formatValue('date', value, config)).trim();
        break;
      case 'time':
      case 'timesec':
        let dateValue;
        if (value.toString().indexOf('-') > 0) {
          dateValue = DateTime.fromISO(value.toString(), { zone: 'utc' });
        } else {
          if (value === '' || value === '0') {
            return '';
          }
          // eslint-disable-next-line radix
          parsedInt = typeof value === 'number' ? value : parseInt(value);
          if (isNaN(parsedInt)) {
            return '';
          }
          dateValue = DateTime.fromSeconds(parsedInt, { zone: 'utc' });
        }
        if (type === 'timesec') {
          theString = dateValue.toFormat('HH:mm:ss');
        } else {
          theString = dateValue.toFormat('HH:mm');
        }
        break;
      case 'password':
        theString = (value) ? FormEngineValidation.passwordDummy : '';
        break;
      default:
        theString = value.toString();
    }
    return theString;
  };

  /**
   * Update input field after change
   */
  FormEngineValidation.updateInputField = function(fieldName: string): void {
    const field = formEngineFormElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;
    const humanReadableField = formEngineFormElement.querySelector(selector`[data-formengine-input-name="${fieldName}"]`) as FormEngineFieldElement;

    if (field.dataset.config !== undefined) {
      const config = JSON.parse(field.dataset.config);
      const evalList = Utility.trimExplode(',', config.evalList);
      let newValue = humanReadableField.value;

      for (let i = 0; i < evalList.length; i++) {
        newValue = FormEngineValidation.processValue(evalList[i], newValue, config);
      }

      let formattedValue = newValue;
      for (let i = 0; i < evalList.length; i++) {
        formattedValue = FormEngineValidation.formatValue(evalList[i], formattedValue, config);
      }

      // Only update fields if value actually changed
      if (field.value !== newValue) {
        if (field.disabled && field.dataset.enableOnModification) {
          field.disabled = false;
        }
        field.value = newValue;
        // After updating the value of the main field, dispatch a "change" event to inform e.g. the "RequestUpdate"
        // component, which always listens to the main field instead of the "human readable field", about it.
        field.dispatchEvent(new Event('change'));
        humanReadableField.value = formattedValue;
      }
    }
  };

  /**
   * Run validation for field
   */
  FormEngineValidation.validateField = function(field: FormEngineFieldElement|JQuery, value?: string): string {
    if (field instanceof $) {
      // @deprecated
      console.warn('Passing a jQuery element to FormEngineValidation.validateField() is deprecated and will be removed in TYPO3 v14.');
      console.trace();
      field = <FormEngineFieldElement>(field as JQuery).get(0);
    }
    if (!(field instanceof HTMLElement)) {
      // Can be removed altogether with jQuery support in TYPO3 v14
      return value;
    }
    value = value || field.value || '';

    if (typeof field.dataset.formengineValidationRules === 'undefined') {
      return value;
    }

    const rules: any = JSON.parse(field.dataset.formengineValidationRules);
    let markParent = false;
    let selected = 0;
    // keep the original value, validateField should not alter it
    const returnValue: string = value;
    let relatedField: FormEngineFieldElement;
    let minItems: number;
    let maxItems: number;

    if (!Array.isArray(value)) {
      value = value.trimStart();
    }

    for (const rule of rules) {
      if (markParent) {
        // abort any further validation as validating the field already failed
        break;
      }
      switch (rule.type) {
        case 'required':
          if (value === '') {
            markParent = true;
            field.closest(FormEngineValidation.markerSelector).classList.add(FormEngineValidation.errorClass);
          }
          break;
        case 'range':
          if (value !== '') {
            if (rule.minItems || rule.maxItems) {
              relatedField = formEngineFormElement.querySelector(selector`[name="${field.dataset.relatedfieldname}"]`) as FormEngineFieldElement;
              if (relatedField !== null) {
                selected = Utility.trimExplode(',', relatedField.value).length;
              } else {
                selected = parseInt(field.value, 10);
              }
              if (rule.minItems !== undefined) {
                minItems = rule.minItems * 1;
                if (!isNaN(minItems) && selected < minItems) {
                  markParent = true;
                }
              }
              if (rule.maxItems !== undefined) {
                maxItems = rule.maxItems * 1;
                if (!isNaN(maxItems) && selected > maxItems) {
                  markParent = true;
                }
              }
            }
            if (rule.lower !== undefined) {
              const minValue = rule.lower * 1;
              if (!isNaN(minValue) && parseInt(value, 10) < minValue) {
                markParent = true;
              }
            }
            if (rule.upper !== undefined) {
              const maxValue = rule.upper * 1;
              if (!isNaN(maxValue) && parseInt(value, 10) > maxValue) {
                markParent = true;
              }
            }
          }
          break;
        case 'select':
        case 'category':
          if (rule.minItems || rule.maxItems) {
            relatedField = formEngineFormElement.querySelector(selector`[name="${field.dataset.relatedfieldname}"]`) as FormEngineFieldElement;
            if (relatedField !== null) {
              selected = Utility.trimExplode(',', relatedField.value).length;
            } else if (field instanceof HTMLSelectElement) {
              selected = field.querySelectorAll('option:checked').length;
            } else {
              selected = field.querySelectorAll('input[value]:checked').length;
            }

            if (rule.minItems !== undefined) {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (rule.maxItems !== undefined) {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'group':
        case 'folder':
          if (rule.minItems || rule.maxItems) {
            selected = Utility.trimExplode(',', field.value).length;
            if (rule.minItems !== undefined) {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (rule.maxItems !== undefined) {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'inline':
          if (rule.minItems || rule.maxItems) {
            selected = Utility.trimExplode(',', field.value).length;
            if (rule.minItems !== undefined) {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (rule.maxItems !== undefined) {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'min':
          if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
            if (field.value.length > 0 && field.value.length < field.minLength) {
              markParent = true;
            }
          }
          break;
        case 'null':
          // unknown type null, we ignore it
          break;
        default:
          break;
      }
    }

    const isValid = !markParent;
    const validationMarker = field.closest(FormEngineValidation.markerSelector);
    if (validationMarker !== null) {
      // Validation marker may be unavailable (e.g. due to maximized ckeditor)
      validationMarker.classList.toggle(FormEngineValidation.errorClass, !isValid);
    }

    FormEngineValidation.markParentTab(field, isValid);
    formEngineFormElement.dispatchEvent(new CustomEvent('t3-formengine-postfieldvalidation', { cancelable: false, bubbles: true }));

    return returnValue;
  };

  /**
   * Process a value by given command and config
   */
  FormEngineValidation.processValue = function(command: string, value: string, config: {is_in: string}): string {
    let newString = '';
    let theValue = '';
    let a = 0;
    let returnValue = value;
    switch (command) {
      case 'alpha':
      case 'num':
      case 'alphanum':
      case 'alphanum_x':
        newString = '';
        for (a = 0; a < value.length; a++) {
          const theChar = value.substr(a, 1);
          let special = (theChar === '_' || theChar === '-');
          let alpha = (theChar >= 'a' && theChar <= 'z') || (theChar >= 'A' && theChar <= 'Z');
          let num = (theChar >= '0' && theChar <= '9');
          switch (command) {
            case 'alphanum':
              special = false;
              break;
            case 'alpha':
              num = false;
              special = false;
              break;
            case 'num':
              alpha = false;
              special = false;
              break;
            default:
              break;
          }
          if (alpha || num || special) {
            newString += theChar;
          }
        }
        if (newString !== value) {
          returnValue = newString;
        }
        break;
      case 'is_in':
        if (config.is_in) {
          theValue = '' + value;
          // Escape special characters, see https://stackoverflow.com/a/6969486/4828813
          config.is_in = config.is_in.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
          const re = new RegExp('[^' + config.is_in + ']+', 'g');
          newString = theValue.replace(re, '');
        } else {
          newString = theValue;
        }
        returnValue = newString;
        break;
      case 'nospace':
        returnValue = ('' + value).replace(/ /g, '');
        break;
      case 'md5':
        if (value !== '') {
          returnValue = Md5.hash(value);
        }
        break;
      case 'upper':
        returnValue = value.toUpperCase();
        break;
      case 'lower':
        returnValue = value.toLowerCase();
        break;
      case 'integer':
        if (value !== '') {
          returnValue = FormEngineValidation.parseInt(value);
        }
        break;
      case 'decimal':
        if (value !== '') {
          returnValue = FormEngineValidation.parseDouble(value);
        }
        break;
      case 'trim':
        returnValue = String(value).trim();
        break;
      case 'datetime':
        if (value !== '') {
          returnValue = FormEngineValidation.parseDateTime(value);
        }
        break;
      case 'date':
        if (value !== '') {
          returnValue = FormEngineValidation.parseDate(value);
        }
        break;
      case 'time':
      case 'timesec':
        if (value !== '') {
          returnValue = FormEngineValidation.parseTime(value, command);
        }
        break;
      case 'year':
        if (value !== '') {
          returnValue = FormEngineValidation.parseYear(value);
        }
        break;
      case 'null':
        // unknown type null, we ignore it
        break;
      case 'password':
        // password is only a display evaluation, we ignore it
        break;
      default:
        if (customEvaluations.has(command)) {
          returnValue = customEvaluations.get(command).call(null, value);
        } else if (typeof TBE_EDITOR === 'object' && TBE_EDITOR.customEvalFunctions !== undefined && typeof TBE_EDITOR.customEvalFunctions[command] === 'function') {
          returnValue = TBE_EDITOR.customEvalFunctions[command](value);
        }
    }
    return returnValue;
  };

  /**
   * Validate the complete form
   */
  FormEngineValidation.validate = function(section?: Element): void {
    if (typeof section === 'undefined' || section instanceof Document) {
      formEngineFormElement.querySelectorAll(FormEngineValidation.markerSelector + ', .t3js-tabmenu-item').forEach((tabMenuItem: HTMLElement): void => {
        tabMenuItem.classList.remove(FormEngineValidation.errorClass, 'has-validation-error')
      });
    }

    const sectionElement = section || document;
    for (const field of sectionElement.querySelectorAll<FormEngineFieldElement>(FormEngineValidation.rulesSelector)) {
      if (field.closest('.t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted') === null) {
        let modified = false;
        const currentValue = field.value;
        const newValue = FormEngineValidation.validateField(field, currentValue);
        if (Array.isArray(newValue) && Array.isArray(currentValue)) {
          // handling for multi-selects
          if (newValue.length !== currentValue.length) {
            modified = true;
          } else {
            for (let i = 0; i < newValue.length; i++) {
              if (newValue[i] !== currentValue[i]) {
                modified = true;
                break;
              }
            }
          }
        } else if (newValue.length && currentValue !== newValue) {
          modified = true;
        }
        if (modified) {
          if (field.disabled && field.dataset.enableOnModification) {
            field.disabled = false;
          }
          field.value = newValue;
        }
      }
    }
  };

  /**
   * Helper function to mark a field as changed.
   */
  FormEngineValidation.markFieldAsChanged = function(field: FormEngineFieldElement|JQuery): void {
    if (field instanceof $) {
      // @deprecated
      console.warn('Passing a jQuery element to FormEngineValidation.markFieldAsChanged() is deprecated and will be removed in TYPO3 v14.');
      console.trace();
      field = <FormEngineFieldElement>(field as JQuery).get(0);
    }
    if (!(field instanceof HTMLElement)) {
      // Can be removed altogether with jQuery support in TYPO3 v14
      return;
    }
    const paletteField = field.closest('.t3js-formengine-palette-field');
    if (paletteField !== null) {
      paletteField.classList.add('has-change');
    }
  };

  /**
   * Parse value to integer
   */
  FormEngineValidation.parseInt = function(value: number|string|boolean): number {
    const theVal = '' + value;

    if (!value) {
      return 0;
    }

    const returnValue = parseInt(theVal, 10);
    if (isNaN(returnValue)) {
      return 0;
    }
    return returnValue;
  };

  /**
   * Parse value to double
   */
  FormEngineValidation.parseDouble = function(value: number|string|boolean, precision: number = 2): string {
    let theVal = '' + value;
    theVal = theVal.replace(/[^0-9,.-]/g, '');
    const negative = theVal.startsWith('-');
    theVal = theVal.replace(/-/g, '');
    theVal = theVal.replace(/,/g, '.');
    if (theVal.indexOf('.') === -1) {
      theVal += '.0';
    }
    const parts = theVal.split('.');
    const dec = parts.pop();
    let theNumberVal = Number(parts.join('') + '.' + dec);
    if (negative) {
      theNumberVal *= -1;
    }
    theVal = theNumberVal.toFixed(precision);

    return theVal;
  };

  /**
   * Parse datetime value
   */
  FormEngineValidation.parseDateTime = function(value: string): number {
    const index = value.indexOf(' ');
    if (index !== -1) {
      const dateVal = FormEngineValidation.parseDate(value.substring(index + 1));
      FormEngineValidation.lastTime = dateVal + FormEngineValidation.parseTime(value.substring(0, index), 'time');
    } else {
      // only date, no time
      FormEngineValidation.lastTime = FormEngineValidation.parseDate(value);
    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse date value
   */
  FormEngineValidation.parseDate = function(value: string): number {
    FormEngineValidation.lastDate = DateTime.fromFormat(value, 'dd-MM-yyyy', { zone: 'utc' }).toUnixInteger();

    return FormEngineValidation.lastDate;
  };

  /**
   * Parse time value
   */
  FormEngineValidation.parseTime = function(value: string, type: string): number {
    const format = type === 'timesec' ? 'HH:mm:ss' : 'HH:mm';
    FormEngineValidation.lastTime = DateTime.fromFormat(value, format, { zone: 'utc' }).set({
      year: 1970,
      month: 1,
      day: 1
    }).toUnixInteger();
    if (FormEngineValidation.lastTime < 0) {
      FormEngineValidation.lastTime += 24 * 60 * 60;
    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse year value
   */
  FormEngineValidation.parseYear = function(value: string): number {
    let year = parseInt(value, 10);
    if (isNaN(year)) {
      year = FormEngineValidation.getYear(new Date());
    }

    FormEngineValidation.lastYear = year;
    return FormEngineValidation.lastYear;
  };

  /**
   * Get year from date object
   */
  FormEngineValidation.getYear = function(timeObj: Date|null): number|null {
    if (timeObj === null) {
      return null;
    }
    return timeObj.getUTCFullYear();
  };

  /**
   * Get date as timestamp from Date object
   */
  FormEngineValidation.getDate = function(timeObj: Date): number {
    const theTime = new Date(FormEngineValidation.getYear(timeObj), timeObj.getUTCMonth(), timeObj.getUTCDate());
    return FormEngineValidation.getTimestamp(theTime);
  };

  FormEngineValidation.pol = function(foreign: string, value: string): object {
    // @todo deprecate
    // eslint-disable-next-line no-eval
    return eval(((foreign == '-') ? '-' : '') + value);
  };

  /**
   * Parse date string or object and return unix timestamp
   */
  FormEngineValidation.getTimestamp = function(timeObj: string|Date): number {
    return Date.parse(timeObj instanceof Date ? timeObj.toISOString() : timeObj) / 1000;
  };

  /**
   * Seconds since midnight
   */
  FormEngineValidation.getTime = function(timeObj: Date): number {
    return timeObj.getUTCHours() * 60 * 60 + timeObj.getUTCMinutes() * 60 + FormEngineValidation.getSecs(timeObj);
  };

  FormEngineValidation.getSecs = function(timeObj: Date): number {
    return timeObj.getUTCSeconds();
  };

  FormEngineValidation.getTimeSecs = function(timeObj: Date): number {
    return timeObj.getHours() * 60 * 60 + timeObj.getMinutes() * 60 + timeObj.getSeconds();
  };

  /**
   * Find tab by field and mark it as has-validation-error
   */
  FormEngineValidation.markParentTab = function(element: FormEngineFieldElement, isValid: boolean): void {
    const panes = DomHelper.parents(element, '.tab-pane');
    panes.forEach((pane: HTMLElement): void => {
      if (isValid) {
        // If incoming element is valid, check for errors in the same sheet
        isValid = pane.querySelector('.has-error') === null;
      }

      const id = pane.id;
      formEngineFormElement
        .querySelector('a[href="#' + id + '"]')
        .closest('.t3js-tabmenu-item')
        .classList.toggle('has-validation-error', !isValid);
    });
  };

  FormEngineValidation.registerSubmitCallback = function () {
    DocumentSaveActions.getInstance().addPreSubmitCallback((): boolean => {
      if (document.querySelector('.' + FormEngineValidation.errorClass) === null) {
        return true;
      }

      const modal = Modal.confirm(
        TYPO3.lang.alert || 'Alert',
        TYPO3.lang['FormEngine.fieldsMissing'],
        Severity.error,
        [
          {
            text: TYPO3.lang['button.ok'] || 'OK',
            active: true,
            btnClass: 'btn-default',
            name: 'ok',
          },
        ]
      );
      modal.addEventListener('button.clicked', () => modal.hideModal());

      return false;
    });
  };

  return FormEngineValidation;
})();
