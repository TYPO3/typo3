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
import { selector } from '@typo3/core/literals';

type CustomEvaluationCallback = (value: string) => string;
type FormEngineInputParams = { field: string, evalList?: string, is_in?: string };

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
      const $field = $(selector`[name="${fieldName}"]`);

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
    const $field = $(selector`[name="${fieldName}"]`);
    const $humanReadableField = $(selector`[data-formengine-input-name="${fieldName}"]`);
    let $mainField = $(selector`[name="${$field.data('main-field')}"]`);
    if ($mainField.length === 0) {
      $mainField = $field;
    }

    const config = $mainField.data('config');
    if (typeof config !== 'undefined') {
      const value = FormEngineValidation.formatByEvals(config, $field.val());
      if (value.length) {
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
  FormEngineValidation.registerCustomEvaluation = function(name: string, handler: CustomEvaluationCallback): void {
    if (!customEvaluations.has(name)) {
      customEvaluations.set(name, handler);
    }
  };

  FormEngineValidation.formatByEvals = function(config: FormEngineInputParams, value: string): string {
    if (config.evalList !== undefined) {
      const evalList = FormEngineValidation.trimExplode(',', config.evalList);
      for (const evalInstruction of evalList) {
        value = FormEngineValidation.formatValue(evalInstruction, value);
      }
    }
    return value;
  };

  /**
   * Format field value
   */
  FormEngineValidation.formatValue = function(type: string, value: string|number): string {
    let theString = '';
    switch (type) {
      case 'date':
      case 'datetime':
      case 'time':
      case 'timesec':
        // if value is '0' (string), it's supposed to be empty
        if (value === '' || value === '0') {
          return '';
        }

        const isoDt = DateTime.fromISO(String(value), { zone: 'utc' });
        if (isoDt.isValid) {
          return isoDt.toISO({ suppressMilliseconds: true });
        }

        const parsedInt = typeof value === 'number' ? value : parseInt(value, 10);
        if (isNaN(parsedInt)) {
          theString = '';
        } else {
          const dt = DateTime.fromSeconds(parsedInt, { zone: 'utc' });
          theString = dt.toISO({ suppressMilliseconds: true });
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
   *
   * @param {String} fieldName
   */
  FormEngineValidation.updateInputField = function(fieldName: string): void {

    const $field = $(selector`[name="${fieldName}"]`);
    let $mainField = $(selector`[name="${$field.data('main-field')}"]`);
    if ($mainField.length === 0) {
      $mainField = $field;
    }
    const $humanReadableField = $(selector`[data-formengine-input-name="${$mainField.attr('name')}"]`);

    const config = $mainField.data('config');
    if (typeof config !== 'undefined') {
      const newValue = FormEngineValidation.processByEvals(config, $humanReadableField.val());
      const formattedValue = FormEngineValidation.formatByEvals(config, newValue);

      if ($mainField.prop('disabled') && $mainField.data('enableOnModification')) {
        $mainField.prop('disabled', false);
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

    const field = <HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>(_field instanceof $ ? (<JQuery>_field).get(0) : _field);

    value = value || field.value || '';

    if (typeof field.dataset.formengineValidationRules === 'undefined') {
      return value;
    }

    const rules: any = JSON.parse(field.dataset.formengineValidationRules);
    let markParent = false;
    let selected = 0;
    // keep the original value, validateField should not alter it
    const returnValue: string = value;
    let $relatedField: JQuery;
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
              $relatedField = $(document).find(selector`[name="${field.dataset.relatedfieldname}"]`);
              if ($relatedField.length) {
                selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
              } else {
                selected = parseInt(field.value, 10);
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
              if (!isNaN(minValue) && parseInt(value, 10) < minValue) {
                markParent = true;
              }
            }
            if (typeof rule.upper !== 'undefined') {
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
            $relatedField = $(document).find(selector`[name="${field.dataset.relatedfieldname}"]`);
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
        case 'folder':
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

    FormEngineValidation.markParentTab($(field), isValid);

    $(document).trigger('t3-formengine-postfieldvalidation');

    return returnValue;
  };

  FormEngineValidation.processByEvals = function(config: FormEngineInputParams, value: string): string {
    if (config.evalList !== undefined) {
      const evalList = FormEngineValidation.trimExplode(',', config.evalList);
      for (const evalInstruction of evalList) {
        value = FormEngineValidation.processValue(evalInstruction, value, config);
      }
    }
    return value;
  };

  /**
   * Process a value by given command and config
   *
   * @param {String} command
   * @param {String} value
   * @param {Array} config
   * @returns {String}
   */
  FormEngineValidation.processValue = function(command: string, value: string, config: FormEngineInputParams): string {
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
      case 'time':
      case 'timesec':
        if (value !== '') {
          const dt = DateTime.fromISO(value, { zone: 'utc' }).set({
            year: 1970,
            month: 1,
            day: 1
          });
          returnValue = dt.toISO({ suppressMilliseconds: true });
        }
        break;
      case 'year':
        if (value !== '') {
          let year = parseInt(value, 10);
          if (isNaN(year)) {
            year = new Date().getUTCFullYear();
          }
          returnValue = year.toString(10);
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
    for (const field of sectionElement.querySelectorAll(FormEngineValidation.rulesSelector)) {
      const $field = $(field);

      if (!$field.closest('.t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted').length) {
        let modified = false;
        const currentValue = $field.val();
        const newValue = FormEngineValidation.validateField($field, currentValue);
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
          if ($field.prop('disabled') && $field.data('enableOnModification')) {
            $field.prop('disabled', false);
          }
          $field.val(newValue);
        }
      }
    }
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
    if (paletteField !== null) {
      paletteField.classList.add('has-change');
    }
  };

  /**
   * Helper function to get clean trimmed array from comma list
   *
   * @param {String} delimiter
   * @param {String} string
   * @returns {Array}
   */
  FormEngineValidation.trimExplode = function(delimiter: string, string: string): string[] {
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
   *
   * @param {String} value
   * @param {Number} precision
   * @returns {String}
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

  FormEngineValidation.pol = function(foreign: string, value: string): object {
    // @todo deprecate
    // eslint-disable-next-line no-eval
    return eval(((foreign == '-') ? '-' : '') + value);
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

  FormEngineValidation.registerSubmitCallback = function () {
    DocumentSaveActions.getInstance().addPreSubmitCallback(function (e: Event) {
      if ($('.' + FormEngineValidation.errorClass).length > 0) {
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

        e.stopImmediatePropagation();
      }
    });
  };

  return FormEngineValidation;
})();
