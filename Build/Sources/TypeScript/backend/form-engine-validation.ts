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
import { DateTime } from 'luxon';
import Md5 from '@typo3/backend/hashing/md5';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import Utility from './utility';
import RegularEvent from '@typo3/core/event/regular-event';
import DomHelper from '@typo3/backend/utility/dom-helper';
import { selector } from '@typo3/core/literals';
import SubmitInterceptor from '@typo3/backend/form/submit-interceptor';
import { FormEngineReview } from '@typo3/backend/form-engine-review';
import type FormEngine from '@typo3/backend/form-engine';
import type { FormEngineFieldElement } from '@typo3/backend/form-engine';

type CustomEvaluationCallback = (value: string) => string;
type FormEngineInputParams = { field: string, evalList?: string, is_in?: string };

export interface PostValidationEvent {
  field: FormEngineFieldElement,
  isValid: boolean,
}

let formEngineInstance: typeof FormEngine;
let validationSuspended = false;

const customEvaluations: Map<string, CustomEvaluationCallback> = new Map();

/**
 * The main FormEngineValidation object
 *
 * @exports @typo3/backend/form-engine-validation
 */
export default class FormEngineValidation {

  public static rulesSelector: string = '[data-formengine-validation-rules]';
  public static inputSelector: string = '[data-formengine-input-params]';
  public static markerSelector: string = '.t3js-formengine-validation-marker';
  public static labelSelector: string = '.t3js-formengine-label';
  public static errorClass: string = 'has-error';
  public static validationErrorClass: string = 'has-validation-error';
  public static passwordDummy: string = '********';

  /**
   * Initialize validation for the first time
   */
  public static initialize(formEngine: typeof FormEngine): void {
    formEngineInstance = formEngine;
    formEngineInstance.formElement.querySelectorAll('.' + FormEngineValidation.errorClass).forEach((e: HTMLElement) => e.classList.remove(FormEngineValidation.errorClass));

    // Initialize input fields
    FormEngineValidation.initializeInputFields();

    new FormEngineReview(formEngineInstance.formElement);

    // Bind to field changes
    new RegularEvent('change', (e: Event, target: FormEngineFieldElement): void => {
      FormEngineValidation.validateField(target);
      formEngineInstance.markFieldAsChanged(target);
    }).delegateTo(formEngineInstance.formElement, FormEngineValidation.rulesSelector);

    FormEngineValidation.registerSubmitCallback();

    FormEngineValidation.validate();
  }

  /**
   * Initialize all input fields
   */
  public static initializeInputFields(): void {
    formEngineInstance.formElement.querySelectorAll(FormEngineValidation.inputSelector).forEach((visibleField: FormEngineFieldElement): void => {
      // ignore fields which already have been initialized
      if ('formengineInputInitialized' in visibleField.dataset) {
        return;
      }

      const config = JSON.parse(visibleField.dataset.formengineInputParams);
      const fieldName = config.field;
      const actualValueField = formEngineInstance.formElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;

      actualValueField.dataset.config = visibleField.dataset.formengineInputParams;
      FormEngineValidation.initializeInputField(fieldName);
    });
  }

  /**
   * Initialize field by name
   */
  public static initializeInputField(fieldName: string): void {
    const field = formEngineInstance.formElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;
    const humanReadableField = formEngineInstance.formElement.querySelector(selector`[data-formengine-input-name="${fieldName}"]`) as FormEngineFieldElement;

    if (field.dataset.config !== undefined) {
      const config = JSON.parse(field.dataset.config);
      const value = FormEngineValidation.formatByEvals(config, field.value);
      if (value.length) {
        humanReadableField.value = value;
      }
    }

    new RegularEvent('change', (): void => {
      FormEngineValidation.updateInputField(humanReadableField.dataset.formengineInputName);
    }).bindTo(humanReadableField);

    // add the attribute so that acceptance tests can know when the field initialization has completed
    humanReadableField.dataset.formengineInputInitialized = 'true';
  }

  public static registerCustomEvaluation(name: string, handler: CustomEvaluationCallback): void {
    if (!customEvaluations.has(name)) {
      customEvaluations.set(name, handler);
    }
  }

  public static formatByEvals(config: FormEngineInputParams, value: string): string {
    if (config.evalList !== undefined) {
      const evalList = Utility.trimExplode(',', config.evalList);
      for (const evalInstruction of evalList) {
        value = FormEngineValidation.formatValue(evalInstruction, value);
      }
    }
    return value;
  }

  /**
   * Format field value
   */
  public static formatValue(type: string, value: string|number): string {
    switch (type) {
      case 'date':
      case 'datetime':
      case 'time':
      case 'timesec':
        if (value === '') {
          return '';
        }
        const isoDt = DateTime.fromISO(String(value));
        if (!isoDt.isValid) {
          throw new Error('Invalid ISO8601 DateTime string: ' + value);
        }
        return isoDt.toISO({ suppressMilliseconds: true, includeOffset: false });
      case 'password':
        return (value) ? FormEngineValidation.passwordDummy : '';
      default:
        return value.toString();
    }
  }

  /**
   * Update input field after change
   */
  public static updateInputField(fieldName: string): void {
    const field = formEngineInstance.formElement.querySelector(selector`[name="${fieldName}"]`) as HTMLInputElement;
    const humanReadableField = formEngineInstance.formElement.querySelector(selector`[data-formengine-input-name="${fieldName}"]`) as FormEngineFieldElement;

    if (field.dataset.config !== undefined) {
      const config = JSON.parse(field.dataset.config);
      const newValue = FormEngineValidation.processByEvals(config, humanReadableField.value);
      const formattedValue = FormEngineValidation.formatByEvals(config, newValue);

      // Only update value field if value actually changed
      if (field.value !== newValue) {
        if (field.disabled && field.dataset.enableOnModification) {
          field.disabled = false;
        }
        field.value = newValue;
        // After updating the value of the main field, dispatch a "change" event to inform e.g. the "RequestUpdate"
        // component, which always listens to the main field instead of the "human readable field", about it.
        field.dispatchEvent(new Event('change'));
      }

      // Synchronize the "human-readable field" as the data normalization may have cleared invalid characters
      if (humanReadableField.value !== formattedValue) {
        humanReadableField.value = formattedValue;
      }
    }
  }

  /**
   * Run validation for field
   */
  public static validateField(field: FormEngineFieldElement): void {
    if (field.dataset.formengineValidationRules === undefined) {
      return;
    }

    let value = field.value || '';

    const rules: any = JSON.parse(field.dataset.formengineValidationRules);
    let markParent = false;
    let selected = 0;
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
            field.classList.add(FormEngineValidation.errorClass);
            field.closest(FormEngineValidation.markerSelector)?.querySelector(FormEngineValidation.labelSelector)?.classList.add(FormEngineValidation.errorClass);
          }
          break;
        case 'range':
          if (value !== '') {
            if (rule.minItems || rule.maxItems) {
              relatedField = formEngineInstance.formElement.querySelector(selector`[name="${field.dataset.relatedfieldname}"]`) as FormEngineFieldElement;
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
              if (field.dataset.inputType === 'datetimepicker') {
                // HEADS up: value and range.lower are both fake UTC-0 ISO8601 strings (they are not UTC-0, but actually server localtime!)
                // But it's fine to compare them in UTC for this comparison and not map to localtime as in date-time-picker parseDate.
                const dt = DateTime.fromISO(value, { zone: 'utc' });
                const lower = DateTime.fromISO(rule.lower, { zone: 'utc' });
                if (!dt.isValid || dt < lower.minus(lower.second * 1000)) {
                  markParent = true;
                }
              } else {
                const minValue = rule.lower * 1;
                if (!isNaN(minValue) && parseInt(value, 10) < minValue) {
                  markParent = true;
                }
              }
            }
            if (rule.upper !== undefined) {
              if (field.dataset.inputType === 'datetimepicker') {
                // HEADS up: value and range.upper are both fake UTC-0 ISO8601 strings (they are not UTC-0, but actually server localtime!)
                // But it's fine to compare them in UTC-0 for this comparison and not map to localtime as in date-time-picker parseDate.
                const dt = DateTime.fromISO(value, { zone: 'utc' });
                const upper = DateTime.fromISO(rule.upper, { zone: 'utc' });
                if (!dt.isValid || dt > upper.plus((59 - upper.second) * 1000)) {
                  markParent = true;
                }
              } else {
                const maxValue = rule.upper * 1;
                if (!isNaN(maxValue) && parseInt(value, 10) > maxValue) {
                  markParent = true;
                }
              }
            }
          }
          break;
        case 'select':
        case 'category':
          if (rule.minItems || rule.maxItems) {
            relatedField = formEngineInstance.formElement.querySelector(selector`[name="${field.dataset.relatedfieldname}"]`) as FormEngineFieldElement;
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
    field.classList.toggle(FormEngineValidation.errorClass, !isValid);
    field.closest(FormEngineValidation.markerSelector)?.querySelector(FormEngineValidation.labelSelector)?.classList.toggle(FormEngineValidation.errorClass, !isValid);

    FormEngineValidation.markParentTab(field, isValid);
    formEngineInstance.formElement.dispatchEvent(new CustomEvent<PostValidationEvent>('t3-formengine-postfieldvalidation', { detail: { field: field, isValid: isValid }, cancelable: false, bubbles: true }));
  }

  public static processByEvals(config: FormEngineInputParams, value: string): string {
    if (config.evalList !== undefined) {
      const evalList = Utility.trimExplode(',', config.evalList);
      for (const evalInstruction of evalList) {
        value = FormEngineValidation.processValue(evalInstruction, value, config);
      }
    }
    return value;
  }

  /**
   * Process a value by given command and config
   */
  public static processValue(command: string, value: string, config: FormEngineInputParams): string {
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
          returnValue = FormEngineValidation.parseInt(value).toString();
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
          const dt = DateTime.fromISO(value).set({
            year: 1970,
            month: 1,
            day: 1
          });
          returnValue = dt.toISO({ suppressMilliseconds: true, includeOffset: false });
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
        } else if (typeof TBE_EDITOR === 'object' && TBE_EDITOR.customEvalFunctions !== undefined && typeof TBE_EDITOR.customEvalFunctions[command] === 'function') {
          returnValue = TBE_EDITOR.customEvalFunctions[command](value);
        }
    }
    return returnValue;
  }

  /**
   * Validate the complete form
   */
  public static validate(section?: Element): void {
    if (typeof section === 'undefined' || section instanceof Document) {
      formEngineInstance.formElement.querySelectorAll(FormEngineValidation.markerSelector + ', .t3js-tabmenu-item').forEach((tabMenuItem: HTMLElement): void => {
        tabMenuItem.classList.remove(FormEngineValidation.validationErrorClass)
      });
    }

    const sectionElement = section || document;
    for (const field of sectionElement.querySelectorAll<FormEngineFieldElement>(FormEngineValidation.rulesSelector)) {
      if (field.closest('.t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted') === null) {
        FormEngineValidation.validateField(field);
      }
    }
  }

  /**
   * Helper function to mark a field as changed.
   *
   * @deprecated
   */
  public static markFieldAsChanged(field: FormEngineFieldElement): void {
    console.warn('Calling markFieldAsChanged() from \'@typo3/backend/form-engine-validation\' is deprecated and will be removed in TYPO3 v15. Instead, call the method from \'@typo3/backend/form-engine\'.');

    formEngineInstance.markFieldAsChanged(field);
  }

  /**
   * Parse value to integer
   */
  public static parseInt(value: number|string|boolean): number {
    const theVal = '' + value;

    if (!value) {
      return 0;
    }

    const returnValue = parseInt(theVal, 10);
    if (isNaN(returnValue)) {
      return 0;
    }
    return returnValue;
  }

  /**
   * Parse value to double
   */
  public static parseDouble(value: number|string|boolean, precision: number = 2): string {
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
  }

  /**
   * Find tab by field and mark it as has-validation-error
   */
  public static markParentTab(element: FormEngineFieldElement, isValid: boolean): void {
    const panes = DomHelper.parents(element, '.tab-pane');
    panes.forEach((pane: HTMLElement): void => {
      if (isValid) {
        // If incoming element is valid, check for errors in the same sheet
        isValid = pane.querySelector('.has-error') === null;
      }

      const id = pane.id;
      formEngineInstance.formElement
        .querySelector('[data-bs-target="#' + id + '"]')
        .closest('.t3js-tabmenu-item')
        .classList.toggle(FormEngineValidation.validationErrorClass, !isValid);
    });
  }

  /**
   * @internal
   */
  public static suspend() {
    validationSuspended = true;
  }

  /**
   * @internal
   */
  public static resume() {
    validationSuspended = false;
  }

  public static isValid(): boolean {
    return document.querySelector('.' + FormEngineValidation.errorClass) === null;
  }

  public static showErrorModal(): void {
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
  }

  public static registerSubmitCallback() {
    const submitInterceptor = new SubmitInterceptor(formEngineInstance.formElement);
    submitInterceptor.addPreSubmitCallback((): boolean => {
      if (validationSuspended || FormEngineValidation.isValid()) {
        return true;
      }

      FormEngineValidation.showErrorModal();

      return false;
    });
  }
}
