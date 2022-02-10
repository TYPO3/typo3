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
import moment from 'moment';
import Md5 from '@typo3/backend/hashing/md5';
import DocumentSaveActions from '@typo3/backend/document-save-actions';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';

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

  /**
   * @type {Map<string, Function>}
   */
  const customEvaluations = new Map();

  /**
   * Initialize validation for the first time
   */
  FormEngineValidation.initialize = function(): void {
    $(document).find('.' + FormEngineValidation.errorClass).removeClass(FormEngineValidation.errorClass);

    // Initialize input fields
    FormEngineValidation.initializeInputFields().promise().done(function() {
      // Bind to field changes
      $(document).on('change', FormEngineValidation.rulesSelector, (event: JQueryEventObject) => {
        FormEngineValidation.validateField(event.currentTarget);
        FormEngineValidation.markFieldAsChanged(event.currentTarget);
      });

      FormEngineValidation.registerSubmitCallback();
    });

    const today = new Date();
    FormEngineValidation.lastYear = FormEngineValidation.getYear(today);
    FormEngineValidation.lastDate = FormEngineValidation.getDate(today);
    FormEngineValidation.lastTime = 0;
    FormEngineValidation.validate();
  };

  /**
   * Initialize all input fields
   *
   * @returns {Object}
   */
  FormEngineValidation.initializeInputFields = function(): JQuery {
    return $(document).find(FormEngineValidation.inputSelector).each(function(index: number, field: HTMLElement) {
      const config = $(field).data('formengine-input-params');
      const fieldName = config.field;
      const $field = $('[name="' + fieldName + '"]');

      // ignore fields which already have been initialized
      if ($field.data('main-field') === undefined) {
        $field.data('main-field', fieldName);
        $field.data('config', config);
        FormEngineValidation.initializeInputField(fieldName);
      }
    });
  };

  /**
   * Initialize field by name
   *
   * @param {String} fieldName
   */
  FormEngineValidation.initializeInputField = function(fieldName: string): void {
    const $field = $('[name="' + fieldName + '"]');
    const $humanReadableField = $('[data-formengine-input-name="' + fieldName + '"]');
    let $mainField = $('[name="' + $field.data('main-field') + '"]');
    if ($mainField.length === 0) {
      $mainField = $field;
    }

    const config = $mainField.data('config');
    if (typeof config !== 'undefined') {
      const evalList = FormEngineValidation.trimExplode(',', config.evalList);
      let value = $field.val();

      for (let i = 0; i < evalList.length; i++) {
        value = FormEngineValidation.formatValue(evalList[i], value, config);
      }
      // Prevent password fields to be overwritten with original value
      if (value.length && $humanReadableField.attr('type') !== 'password') {
        $humanReadableField.val(value);
      }
    }
    $humanReadableField.data('main-field', fieldName);
    $humanReadableField.data('config', config);
    $humanReadableField.on('change', function() {
      FormEngineValidation.updateInputField($humanReadableField.attr('data-formengine-input-name'));
    });

    // add the attribute so that acceptance tests can know when the field initialization has completed
    $humanReadableField.attr('data-formengine-input-initialized', 'true');
  };

  /**
   * @param {string} name
   * @param {Function} handler
   */
  FormEngineValidation.registerCustomEvaluation = function(name: string, handler: Function): void {
    if (!customEvaluations.has(name)) {
      customEvaluations.set(name, handler);
    }
  }

  /**
   * Format field value
   *
   * @param {String} type
   * @param {String|Number} value
   * @param {Object} config
   * @returns {String}
   */
  FormEngineValidation.formatValue = function(type: string, value: string|number, config: Object): string {
    let theString = '';
    let parsedInt: number, theTime: Date;
    switch (type) {
      case 'date':
        // poor man’s ISO-8601 detection: if we have a "-" in it, it apparently is not an integer.
        if (value.toString().indexOf('-') > 0) {
          const date = moment.utc(value);
          theString = date.format('DD-MM-YYYY');
        } else {
          // @ts-ignore
          parsedInt = value * 1;
          if (!parsedInt) {
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
        // eslint-disable-next-line radix
        if (value.toString().indexOf('-') <= 0 && !(typeof value === 'number' ? value : parseInt(value))) {
          return '';
        }
        theString = FormEngineValidation.formatValue('time', value, config) + ' ' + FormEngineValidation.formatValue('date', value, config);
        break;
      case 'time':
      case 'timesec':
        let dateValue;
        if (value.toString().indexOf('-') > 0) {
          dateValue = moment.utc(value);
        } else {
          // eslint-disable-next-line radix
          parsedInt = typeof value === 'number' ? value : parseInt(value);
          if (!parsedInt && value.toString() !== '0') {
            return '';
          }
          dateValue = moment.unix(parsedInt).utc();
        }
        if (type === 'timesec') {
          theString = dateValue.format('HH:mm:ss');
        } else {
          theString = dateValue.format('HH:mm');
        }
        break;
      case 'password':
        theString = (value) ? FormEngineValidation.passwordDummy : '';
        break;
      default:
        // @ts-ignore
        theString = value;
    }
    return theString;
  };

  /**
   * Update input field after change
   *
   * @param {String} fieldName
   */
  FormEngineValidation.updateInputField = function(fieldName: string): void {
    const $field = $('[name="' + fieldName + '"]');
    let $mainField = $('[name="' + $field.data('main-field') + '"]');
    if ($mainField.length === 0) {
      $mainField = $field;
    }
    const $humanReadableField = $('[data-formengine-input-name="' + $mainField.attr('name') + '"]');

    const config = $mainField.data('config');
    if (typeof config !== 'undefined') {
      const evalList = FormEngineValidation.trimExplode(',', config.evalList);
      let newValue = $humanReadableField.val();

      for (let i = 0; i < evalList.length; i++) {
        newValue = FormEngineValidation.processValue(evalList[i], newValue, config);
      }

      let formattedValue = newValue;
      for (let i = 0; i < evalList.length; i++) {
        formattedValue = FormEngineValidation.formatValue(evalList[i], formattedValue, config);
      }

      $mainField.val(newValue);
      // After updating the value of the main field, dispatch a "change" event to inform e.g. the "RequestUpdate"
      // component, which always listens to the main field instead of the "human readable field", about it.
      $mainField.get(0).dispatchEvent(new Event('change'));
      $humanReadableField.val(formattedValue);
    }
  };

  /**
   * Run validation for field
   *
   * @param {HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement|jQuery} field
   * @param {String} [value=$field.val()]
   * @returns {String}
   */
  FormEngineValidation.validateField = function(_field: HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement|JQuery, value?: string): string {

    const field = <HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>(_field instanceof $ ? (<JQuery>_field).get(0) : _field)

    value = value || field.value || '';

    if (typeof field.dataset.formengineValidationRules === 'undefined') {
      return value;
    }

    const rules: any = JSON.parse(field.dataset.formengineValidationRules);
    let markParent = false;
    let selected = 0;
    // keep the original value, validateField should not alter it
    let returnValue: string = value;
    let $relatedField: JQuery;
    let minItems: number;
    let maxItems: number;

    if (!$.isArray(value)) {
      value = FormEngineValidation.ltrim(value);
    }

    $.each(rules, function(k: number, rule: any): void|boolean {
      if (markParent) {
        // abort any further validation as validating the field already failed
        return false;
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
              $relatedField = $(document).find('[name="' + field.dataset.relatedfieldname + '"]');
              if ($relatedField.length) {
                selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
              } else {
                // @ts-ignore
                selected = field.value;
              }
              if (typeof rule.minItems !== 'undefined') {
                minItems = rule.minItems * 1;
                if (!isNaN(minItems) && selected < minItems) {
                  markParent = true;
                }
              }
              if (typeof rule.maxItems !== 'undefined') {
                maxItems = rule.maxItems * 1;
                if (!isNaN(maxItems) && selected > maxItems) {
                  markParent = true;
                }
              }
            }
            if (typeof rule.lower !== 'undefined') {
              const minValue = rule.lower * 1;
              // @ts-ignore
              if (!isNaN(minValue) && value < minValue) {
                markParent = true;
              }
            }
            if (typeof rule.upper !== 'undefined') {
              const maxValue = rule.upper * 1;
              // @ts-ignore
              if (!isNaN(maxValue) && value > maxValue) {
                markParent = true;
              }
            }
          }
          break;
        case 'select':
        case 'category':
          if (rule.minItems || rule.maxItems) {
            $relatedField = $(document).find('[name="' + field.dataset.relatedfieldname + '"]');
            if ($relatedField.length) {
              selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
            } else if (field instanceof HTMLSelectElement) {
              selected = field.querySelectorAll('option:checked').length;
            } else {
              selected = field.querySelectorAll('input[value]:checked').length;
            }

            if (typeof rule.minItems !== 'undefined') {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (typeof rule.maxItems !== 'undefined') {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'group':
          if (rule.minItems || rule.maxItems) {
            selected = FormEngineValidation.trimExplode(',', field.value).length;
            if (typeof rule.minItems !== 'undefined') {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (typeof rule.maxItems !== 'undefined') {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'inline':
          if (rule.minItems || rule.maxItems) {
            selected = FormEngineValidation.trimExplode(',', field.value).length;
            if (typeof rule.minItems !== 'undefined') {
              minItems = rule.minItems * 1;
              if (!isNaN(minItems) && selected < minItems) {
                markParent = true;
              }
            }
            if (typeof rule.maxItems !== 'undefined') {
              maxItems = rule.maxItems * 1;
              if (!isNaN(maxItems) && selected > maxItems) {
                markParent = true;
              }
            }
          }
          break;
        case 'null':
          // unknown type null, we ignore it
          break;
        default:
          break;
      }
    });

    const isValid = !markParent;
    field.closest(FormEngineValidation.markerSelector).classList.toggle(FormEngineValidation.errorClass, !isValid);
    FormEngineValidation.markParentTab($(field), isValid);

    $(document).trigger('t3-formengine-postfieldvalidation');

    return returnValue;
  };

  /**
   * Process a value by given command and config
   *
   * @param {String} command
   * @param {String} value
   * @param {Array} config
   * @returns {String}
   */
  FormEngineValidation.processValue = function(command: string, value: string, config: {is_in: string}): string {
    let newString = '';
    let theValue = '';
    let theCmd = '';
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
          // eslint-disable-next-line @typescript-eslint/quotes
          config.is_in = config.is_in.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
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
      case 'int':
        if (value !== '') {
          returnValue = FormEngineValidation.parseInt(value);
        }
        break;
      case 'double2':
        if (value !== '') {
          returnValue = FormEngineValidation.parseDouble(value);
        }
        break;
      case 'trim':
        returnValue = String(value).trim();
        break;
      case 'datetime':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseDateTime(value);
        }
        break;
      case 'date':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseDate(value);
        }
        break;
      case 'time':
      case 'timesec':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseTime(value, command);
        }
        break;
      case 'year':
        if (value !== '') {
          theCmd = value.substr(0, 1);
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
        } else if (typeof TBE_EDITOR === 'object' && typeof TBE_EDITOR.customEvalFunctions !== 'undefined' && typeof TBE_EDITOR.customEvalFunctions[command] === 'function') {
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
      $(document).find(FormEngineValidation.markerSelector + ', .t3js-tabmenu-item')
        .removeClass(FormEngineValidation.errorClass)
        .removeClass('has-validation-error');
    }

    const sectionElement = section || document;
    $(sectionElement).find(FormEngineValidation.rulesSelector).each((index: number, field: HTMLElement) => {
      const $field = $(field);

      if (!$field.closest('.t3js-flex-section-deleted, .t3js-inline-record-deleted').length) {
        let modified = false;
        const currentValue = $field.val();
        const newValue = FormEngineValidation.validateField($field, currentValue);
        if ($.isArray(newValue) && $.isArray(currentValue)) {
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
          $field.val(newValue);
        }
      }
    });
  };

  /**
   * Helper function to mark a field as changed.
   *
   * @param {HTMLInputElement|HTMLTextAreaElement|jQuery} field
   */
  FormEngineValidation.markFieldAsChanged = function(field: HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement|JQuery): void {
    if (field instanceof $) {
      field = <HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>(field as JQuery).get(0);
    }
    if (!(field instanceof HTMLElement)) {
      return;
    }
    const paletteField = field.closest('.t3js-formengine-palette-field');
    paletteField.classList.add('has-change');
  };

  /**
   * Helper function to get clean trimmed array from comma list
   *
   * @param {String} delimiter
   * @param {String} string
   * @returns {Array}
   */
  FormEngineValidation.trimExplode = function(delimiter: string, string: string): Array<string> {
    const result = [];
    const items = string.split(delimiter);
    for (let i = 0; i < items.length; i++) {
      const item = items[i].trim();
      if (item.length > 0) {
        result.push(item);
      }
    }
    return result;
  };

  /**
   * Parse value to integer
   *
   * @param {(Number|String)} value
   * @returns {Number}
   */
  FormEngineValidation.parseInt = function(value: number|string|boolean): number {
    const theVal = '' + value;
    let returnValue;

    if (!value) {
      return 0;
    }

    returnValue = parseInt(theVal, 10);
    if (isNaN(returnValue)) {
      return 0;
    }
    return returnValue;
  };

  /**
   * Parse value to double
   *
   * @param {String} value
   * @returns {String}
   */
  FormEngineValidation.parseDouble = function(value: number|string|boolean): string {
    let theVal = '' + value;
    theVal = theVal.replace(/[^0-9,\.-]/g, '');
    const negative = theVal.substring(0, 1) === '-';
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
    theVal = theNumberVal.toFixed(2);

    return theVal;
  };

  /**
   * Trims leading whitespace characters
   *
   * @param {String} value
   * @returns {String}
   */
  FormEngineValidation.ltrim = function(value: string): string {
    const theVal = '' + value;
    if (!value) {
      return '';
    }
    return theVal.replace(/^\s+/, '');
  };

  /**
   * Trims trailing whitespace characters
   *
   * @param {String} value
   * @returns {String}
   */
  FormEngineValidation.btrim = function(value: string): string {
    const theVal = '' + value;
    if (!value) {
      return '';
    }
    return theVal.replace(/\s+$/, '');
  };

  /**
   * Parse datetime value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseDateTime = function(value: string): number {
    const index = value.indexOf(' ');
    if (index !== -1) {
      const dateVal = FormEngineValidation.parseDate(value.substr(index, value.length));
      FormEngineValidation.lastTime = dateVal + FormEngineValidation.parseTime(value.substr(0, index), 'time');
    } else {
      // only date, no time
      FormEngineValidation.lastTime = FormEngineValidation.parseDate(value);
    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse date value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseDate = function(value: string): number {
    const today = new Date();
    let values = FormEngineValidation.split(value);

    if (values.values[1] && values.values[1].length > 2) {
      const temp = values.values[1];
      values = FormEngineValidation.splitSingle(temp);
    }

    const year = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : FormEngineValidation.getYear(today);
    const month = (values.values[2]) ? FormEngineValidation.parseInt(values.values[2]) : today.getUTCMonth() + 1;
    const day = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : today.getUTCDate();
    const theTime = moment.utc();
    // eslint-disable-next-line radix
    theTime.year(parseInt(year)).month(parseInt(month) - 1).date(parseInt(day)).hour(0).minute(0).second(0);
    FormEngineValidation.lastDate = theTime.unix();

    return FormEngineValidation.lastDate;
  };

  /**
   * Parse time value
   *
   * @param {String} value
   * @param {String} type
   * @returns {*}
   */
  FormEngineValidation.parseTime = function(value: string, type: string): number {
    const today = new Date();
    let values = FormEngineValidation.split(value);

    if (values.values[1] && values.values[1].length > 2) {
      const temp = values.values[1];
      values = FormEngineValidation.splitSingle(temp);
    }

    const sec = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : today.getUTCSeconds();
    const min = (values.values[2]) ? FormEngineValidation.parseInt(values.values[2]) : today.getUTCMinutes();
    const hour = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : today.getUTCHours();
    const theTime = moment.utc();
    theTime.year(1970).month(0).date(1).hour(hour).minute(min).second(type === 'timesec' ? sec : 0);

    FormEngineValidation.lastTime = theTime.unix();
    if (FormEngineValidation.lastTime < 0) {
      FormEngineValidation.lastTime += 24 * 60 * 60;
    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse year value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseYear = function(value: string): number {
    const today = new Date();
    const values = FormEngineValidation.split(value);

    FormEngineValidation.lastYear = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : FormEngineValidation.getYear(today);
    return FormEngineValidation.lastYear;
  };

  /**
   * Get year from date object
   *
   * @param {Date} timeObj
   * @returns {?number}
   */
  FormEngineValidation.getYear = function(timeObj: Date|null): number|null {
    if (timeObj === null) {
      return null;
    }
    return timeObj.getUTCFullYear();
  };

  /**
   * Get date as timestamp from Date object
   *
   * @param {Date} timeObj
   * @returns {Number}
   */
  FormEngineValidation.getDate = function(timeObj: Date): number {
    const theTime = new Date(FormEngineValidation.getYear(timeObj), timeObj.getUTCMonth(), timeObj.getUTCDate());
    return FormEngineValidation.getTimestamp(theTime);
  };

  /**
   *
   * @param {String} foreign
   * @param {String} value
   * @returns {Object}
   */
  FormEngineValidation.pol = function(foreign: string, value: string): Object {
    // @todo deprecate
    // eslint-disable-next-line no-eval
    return eval(((foreign == '-') ? '-' : '') + value);
  };

  /**
   * Substract timezone offset from client to a timestamp to get UTC-timestamp to be send to server
   *
   * @param {Number} timestamp
   * @param {Number} timeonly
   * @returns {*}
   */
  FormEngineValidation.convertClientTimestampToUTC = function(timestamp: number, timeonly: number): number {
    const timeObj = new Date(timestamp * 1000);
    timeObj.setTime((timestamp - timeObj.getTimezoneOffset() * 60) * 1000);
    if (timeonly) {
      // only seconds since midnight
      return FormEngineValidation.getTime(timeObj);
    } else {
      // seconds since the "unix-epoch"
      return FormEngineValidation.getTimestamp(timeObj);
    }
  };

  /**
   * Parse date string or object and return unix timestamp
   *
   * @param {(String|Date)} timeObj
   * @returns {Number}
   */
  FormEngineValidation.getTimestamp = function(timeObj: string|Date): number {
    return Date.parse(timeObj instanceof Date ? timeObj.toISOString() : timeObj) / 1000;
  };

  /**
   * Seconds since midnight
   *
   * @param timeObj
   * @returns {*}
   */
  FormEngineValidation.getTime = function(timeObj: Date): number {
    return timeObj.getUTCHours() * 60 * 60 + timeObj.getUTCMinutes() * 60 + FormEngineValidation.getSecs(timeObj);
  };

  /**
   *
   * @param timeObj
   * @returns {Number}
   */
  FormEngineValidation.getSecs = function(timeObj: Date): number {
    return timeObj.getUTCSeconds();
  };

  /**
   *
   * @param timeObj
   * @returns {Number}
   */
  FormEngineValidation.getTimeSecs = function(timeObj: Date): number {
    return timeObj.getHours() * 60 * 60 + timeObj.getMinutes() * 60 + timeObj.getSeconds();
  };

  /**
   * Find tab by field and mark it as has-validation-error
   *
   * @param {Object} $element
   * @param {Boolean} isValid
   */
  FormEngineValidation.markParentTab = function($element: JQuery, isValid: boolean): void {
    const $panes = $element.parents('.tab-pane');
    $panes.each(function(index: number, pane: HTMLElement) {
      const $pane = $(pane);
      if (isValid) {
        // If incoming element is valid, check for errors in the same sheet
        isValid = $pane.find('.has-error').length === 0;
      }
      const id = $pane.attr('id');
      $(document)
        .find('a[href="#' + id + '"]')
        .closest('.t3js-tabmenu-item')
        .toggleClass('has-validation-error', !isValid);
    });
  };

  /**
   *
   * @param value
   * @returns {{values: Array, pointer: number}}
   */
  FormEngineValidation.splitSingle = function(value: string): {values: Array<string>, pointer: number} {
    const theVal = '' + value;
    const result = {
      values: <Array<string>>[],
      pointer: 3
    };
    result.values[1] = theVal.substr(0, 2);
    result.values[2] = theVal.substr(2, 2);
    result.values[3] = theVal.substr(4, 10);
    return result;
  };

  /**
   *
   * @param theStr1
   * @param delim
   * @param index
   * @returns {*}
   */
  FormEngineValidation.splitStr = function(theStr1: string, delim: string, index: number): string {
    const theStr = '' + theStr1;
    const lengthOfDelim = delim.length;
    let sPos = -lengthOfDelim;
    if (index < 1) {
      index = 1;
    }
    for (let a = 1; a < index; a++) {
      sPos = theStr.indexOf(delim, sPos + lengthOfDelim);
      if (sPos == -1) {
        return null;
      }
    }
    let ePos = theStr.indexOf(delim, sPos + lengthOfDelim);
    if (ePos == -1) {
      ePos = theStr.length;
    }
    return (theStr.substring(sPos + lengthOfDelim, ePos));
  };

  /**
   *
   * @param value
   * @returns {{values: Array, valPol: Array, pointer: number, numberMode: number, theVal: string}}
   */
  FormEngineValidation.split = function(value: string): {
    values: Array<string>,
    valPol: Array<string>,
    pointer: number,
    numberMode: number,
    theVal: string
  } {
    const result = {
      values: <Array<string>>[],
      valPol: <Array<string>>[],
      pointer: 0,
      numberMode: 0,
      theVal: ''
    };
    value += ' ';
    for (let a = 0; a < value.length; a++) {
      const theChar = value.substr(a, 1);
      if (theChar < '0' || theChar > '9') {
        if (result.numberMode) {
          result.pointer++;
          result.values[result.pointer] = result.theVal;
          result.theVal = '';
          result.numberMode = 0;
        }
        if (theChar == '+' || theChar == '-') {
          result.valPol[result.pointer + 1] = theChar;
        }
      } else {
        result.theVal += theChar;
        result.numberMode = 1;
      }
    }
    return result;
  };

  FormEngineValidation.registerSubmitCallback = function () {
    DocumentSaveActions.getInstance().addPreSubmitCallback(function (e: Event) {
      if ($('.' + FormEngineValidation.errorClass).length > 0) {
        Modal.confirm(
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
        ).on('button.clicked', function () {
          Modal.dismiss();
        });

        e.stopImmediatePropagation();
      }
    });
  }

  return FormEngineValidation;
})();
