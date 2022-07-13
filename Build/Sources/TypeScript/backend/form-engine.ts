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
 * contains all JS functions related to TYPO3 TCEforms/FormEngine
 *
 * there are separate issues in this main object
 *   - functions, related to Element Browser ("Popup Window") and select fields
 *   - filling select fields (by wizard etc) from outside, formerly known via "setFormValueFromBrowseWin"
 *   - select fields: move selected items up and down via buttons, remove items etc
 */

/**
 * Module: @typo3/backend/form-engine
 */
import $ from 'jquery';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import DocumentSaveActions from '@typo3/backend/document-save-actions';
import Icons from '@typo3/backend/icons';
import Modal from '@typo3/backend/modal';
import * as MessageUtility from '@typo3/backend/utility/message-utility';
import Severity from '@typo3/backend/severity';
import * as BackendExceptionModule from '@typo3/backend/backend-exception';
import InteractionRequestMap from '@typo3/backend/event/interaction-request-map';
import InteractionRequest from '@typo3/backend/event/interaction-request';
import TriggerRequest from '@typo3/backend/event/trigger-request';
import Utility from '@typo3/backend/utility';

interface OnFieldChangeItem {
  name: string;
  data: {[key: string]: string|number|boolean|null}
}

export default (function() {

  function handleConsumeResponse(interactionRequest: InteractionRequest, response: boolean): void {
    if (response) {
      FormEngine.interactionRequestMap.resolveFor(interactionRequest);
    } else {
      FormEngine.interactionRequestMap.rejectFor(interactionRequest);
    }
  }

  const onFieldChangeHandlers: Map<string, Function> = new Map();

  // @see \TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange
  onFieldChangeHandlers.set('typo3-backend-form-update-value', (data: {elementName: string}, evt: Event) => {
    const valueField = document.querySelector('[name="' + CSS.escape(data.elementName) + '"]');
    const humanReadableField = document.querySelector('[data-formengine-input-name="' + CSS.escape(data.elementName) + '"]');
    FormEngineValidation.updateInputField(data.elementName);
    if (valueField !== null) {
      FormEngineValidation.markFieldAsChanged(valueField as HTMLInputElement);
      FormEngineValidation.validateField(valueField as HTMLInputElement);
    }
    if (humanReadableField !== null && humanReadableField !== valueField) {
      FormEngineValidation.validateField(humanReadableField as HTMLTextAreaElement);
    }
  });
  // @see \TYPO3\CMS\Backend\Form\Behavior\ReloadOnFieldChange
  onFieldChangeHandlers.set('typo3-backend-form-reload', (data: {confirmation: boolean}, evt: Event) => {
    if (!data.confirmation) {
      FormEngine.saveDocument();
      return;
    }
    Modal.confirm(TYPO3.lang['FormEngine.refreshRequiredTitle'], TYPO3.lang['FormEngine.refreshRequiredContent'])
      .on('button.clicked', (evt) => {
        if ((evt.target as HTMLButtonElement).name == 'ok') {
          FormEngine.saveDocument();
        }
        Modal.dismiss();
      });
  });
  // @see \TYPO3\CMS\Backend\Form\Behavior\UpdateBitmaskOnFieldChange
  onFieldChangeHandlers.set('typo3-backend-form-update-bitmask', (data: {position: number, total: number, invert: boolean, elementName: string }, evt: Event) => {
    const targetRef = evt.target; // clicked element
    const elementRef = ((document as any).editform as HTMLFormElement)[data.elementName]; // (hidden) element holding value
    const active = (targetRef as HTMLInputElement).checked !== data.invert; // `xor` either checked or inverted
    const mask = Math.pow(2, data.position);
    const unmask = Math.pow(2, data.total) - mask - 1;
    elementRef.value = active ? (elementRef.value | mask) : (elementRef.value & unmask);
    elementRef.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
  });

  /**
   * @exports @typo3/backend/form-engine
   */
  const FormEngine: any = {
    consumeTypes: ['typo3.setUrl', 'typo3.beforeSetUrl', 'typo3.refresh'],
    Validation: FormEngineValidation,
    interactionRequestMap: InteractionRequestMap,
    formName: TYPO3.settings.FormEngine.formName,
    openedPopupWindow: null,
    legacyFieldChangedCb: function() {
      !$.isFunction(TYPO3.settings.FormEngine.legacyFieldChangedCb) || TYPO3.settings.FormEngine.legacyFieldChangedCb();
    },
    browserUrl: ''
  };

  /**
   * Opens a popup window with the element browser (browser.php)
   *
   * @param {string} mode can be "db" or "file"
   * @param {string} params additional params for the browser window
   * @param {string} entryPoint the entry point, which should be expanded by default
   */
  FormEngine.openPopupWindow = function(mode: string, params: string, entryPoint: string): JQuery {
    return Modal.advanced({
      type: Modal.types.iframe,
      content: FormEngine.browserUrl + '&mode=' + mode + '&bparams=' + params + (entryPoint ? ('&' + (mode === 'db' ? 'expandPage' : 'expandFolder') + '=' + entryPoint) : ''),
      size: Modal.sizes.large
    });
  };

  /**
   * properly fills the select field from the popup window (element browser, link browser)
   * or from a multi-select (two selects side-by-side)
   * previously known as "setFormValueFromBrowseWin"
   *
   * @param {string} fieldName Formerly known as "fsetFormValueFromBrowseWinName" name of the field, like [tt_content][2387][header]
   * @param {string|number} value The value to fill in (could be an integer)
   * @param {string} label The visible name in the selector
   * @param {string} title The title when hovering over it
   * @param {string} exclusiveValues If the select field has exclusive options that are not combine-able
   * @param {HTMLOptionElement} optionEl The HTMLOptionElement object of the selected <option> tag
   */
  FormEngine.setSelectOptionFromExternalSource = function(
    fieldName: string,
    value: string,
    label: string,
    title: string,
    exclusiveValues?: string,
    optionEl?: HTMLOptionElement,
  ): void {
    exclusiveValues = String(exclusiveValues);

    let $fieldEl,
      originalFieldEl,
      isMultiple = false,
      isList = false;

    $fieldEl = FormEngine.getFieldElement(fieldName);
    originalFieldEl = $fieldEl.get(0);

    if (originalFieldEl === null || value === '--div--' || originalFieldEl instanceof HTMLOptGroupElement) {
      return;
    }

    // Check if the form object has a "_list" element
    // The "_list" element exists for multiple selection select types
    const $listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true);
    if ($listFieldEl.length > 0) {
      $fieldEl = $listFieldEl;
      isMultiple = ($fieldEl.prop('multiple') && $fieldEl.prop('size') != '1');
      isList = true;
    }

    if (isMultiple || isList) {
      const $availableFieldEl = FormEngine.getFieldElement(fieldName, '_avail');

      // If multiple values are not allowed, clear anything that is in the control already
      if (!isMultiple) {
        $fieldEl.find('option').each((index: number, el: HTMLElement) => {
          const $option = $availableFieldEl.find('option[value="' + $.escapeSelector($(el).attr('value')) + '"]');
          if ($option.length) {
            $option.removeClass('hidden').prop('disabled', false);
            FormEngine.enableOptGroup($option.get(0));
          }
        });
        $fieldEl.empty();
      }

      // Clear elements if exclusive values are found
      if (exclusiveValues) {
        let reenableOptions = false;

        let m = new RegExp('(^|,)' + value + '($|,)');
        // the new value is exclusive => remove all existing values
        if (exclusiveValues.match(m)) {
          $fieldEl.empty();
          reenableOptions = true;
        } else if ($fieldEl.find('option').length == 1) {
          // there is an old value and it was exclusive => it has to be removed
          m = new RegExp('(^|,)' + $fieldEl.find('option').prop('value') + '($|,)');
          if (exclusiveValues.match(m)) {
            $fieldEl.empty();
            reenableOptions = true;
          }
        }

        if (reenableOptions && typeof optionEl !== 'undefined') {
          optionEl.closest('select').querySelectorAll('[disabled]').forEach(function (disabledOption: HTMLOptionElement) {
            disabledOption.classList.remove('hidden');
            disabledOption.disabled = false;
            FormEngine.enableOptGroup(disabledOption);
          });
        }
      }

      // Inserting the new element
      let addNewValue = true;

      // check if there is a "_mul" field (a field on the right) and if the field was already added
      const $multipleFieldEl = FormEngine.getFieldElement(fieldName, '_mul', true);
      if ($multipleFieldEl.length == 0 || $multipleFieldEl.val() == 0) {
        $fieldEl.find('option').each(function(k: number, optionEl: HTMLOptionElement): void|boolean {
          if ($(optionEl).prop('value') == value) {
            addNewValue = false;
            return false;
          }
        });

        if (addNewValue && typeof optionEl !== 'undefined') {
          optionEl.classList.add('hidden');
          optionEl.disabled = true;
          // In case the disabled option was the last active option and is in an optGroup, also disable the optGroup
          const optGroup = <HTMLOptGroupElement>optionEl.parentElement;
          if (optGroup instanceof HTMLOptGroupElement
            && optGroup.querySelectorAll('option:not([disabled]):not([hidden]):not(.hidden)').length === 0
          ) {
            optGroup.disabled = true;
            optGroup.classList.add('hidden');
          }
        }
      }

      // element can be added
      if (addNewValue) {
        // finally add the option
        const $option = $('<option></option>');
        $option.attr({value: value, title: title}).text(label);
        $option.appendTo($fieldEl);

        // set the hidden field
        FormEngine.updateHiddenFieldValueFromSelect($fieldEl, originalFieldEl);

        // execute the phpcode from $FormEngine->TBE_EDITOR_fieldChanged_func
        FormEngine.legacyFieldChangedCb();
        FormEngineValidation.markFieldAsChanged(originalFieldEl);
        FormEngine.Validation.validateField($fieldEl);
        FormEngine.Validation.validateField($availableFieldEl);
      }

    } else {

      // The incoming value consists of the table name, an underscore and the uid
      // or just the uid
      // For a single selection field we need only the uid, so we extract it
      const pattern = /_(\d+)$/
        , result = value.toString().match(pattern);

      if (result != null) {
        value = result[1];
      }

      // Change the selected value
      $fieldEl.val(value);
      FormEngine.Validation.validateField($fieldEl);
    }
  };

  /**
   * sets the value of the hidden field, from the select list, always executed after the select field was updated
   * previously known as global function setHiddenFromList()
   *
   * @param {HTMLElement} selectFieldEl the select field
   * @param {HTMLElement} originalFieldEl the hidden form field
   */
  FormEngine.updateHiddenFieldValueFromSelect = function(selectFieldEl: HTMLSelectElement, originalFieldEl: HTMLSelectElement): void {
    const selectedValues = <Array<string>>[];
    $(selectFieldEl).find('option').each((index: number, el: HTMLElement) => {
      selectedValues.push($(el).prop('value'));
    });

    // make a comma separated list, if it is a multi-select
    // set the values to the final hidden field
    originalFieldEl.value = selectedValues.join(',');
    originalFieldEl.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
  };

  /**
   * returns a jQuery object for the given form name of the current form,
   * if the parameter "fieldName" is given, then the form element is only returned if the field name is available
   * the latter behaviour mirrors the one of the function "setFormValue_getFObj"
   *
   * @param {String} fieldName the field name to check for, optional
   * @returns {*|HTMLElement}
   */
  FormEngine.getFormElement = function(fieldName: string): JQuery|HTMLElement|void {
    const $formEl = $('form[name="' + FormEngine.formName + '"]:first');
    if (fieldName) {
      const $fieldEl = FormEngine.getFieldElement(fieldName)
        , $listFieldEl = FormEngine.getFieldElement(fieldName, '_list');

      // Take the form object if it is either of type select-one or of type-multiple and it has a "_list" element
      if ($fieldEl.length > 0 &&
        (
          ($fieldEl.prop('type') === 'select-one') ||
          ($listFieldEl.length > 0 && $listFieldEl.prop('type').match(/select-(one|multiple)/))
        )
      ) {
        return $formEl;
      } else {
        console.error('Form fields missing: form: ' + FormEngine.formName + ', field name: ' + fieldName);
        alert('Form field is invalid');
      }
    } else {
      return $formEl;
    }
  };


  /**
   * Returns a jQuery object of the field DOM element of the current form, can also be used to
   * request an alternative field like "_hr", "_list" or "_mul"
   *
   * @param {String} fieldName the name of the field (<input name="fieldName">)
   * @param {String} appendix optional
   * @param {Boolean} noFallback if set, then the appendix value is returned no matter if it exists or not
   * @returns {*|HTMLElement}
   */
  FormEngine.getFieldElement = function(fieldName: string, appendix: string, noFallback: boolean): JQuery|HTMLElement {
    const $formEl = $('form[name="' + FormEngine.formName + '"]:first');

    // if an appendix is set, return the field with the appendix (like _mul or _list)
    if (appendix) {
      let $fieldEl;
      switch (appendix) {
        case '_list':
          $fieldEl = $(':input[data-formengine-input-name="' + fieldName + '"]:not([type=hidden])', $formEl);
          break;
        case '_avail':
          $fieldEl = $(':input[data-relatedfieldname="' + fieldName + '"]', $formEl);
          break;
        case '_mul':
        case '_hr':
          $fieldEl = $(':input[type=hidden][data-formengine-input-name="' + fieldName + '"]', $formEl);
          break;
        default:
          $fieldEl = null;
          break;
      }
      if (($fieldEl && $fieldEl.length > 0) || noFallback === true) {
        return $fieldEl;
      }
    }

    return $(':input[name="' + fieldName + '"]', $formEl);
  };

  /**
   * Initialize events for all form engine relevant tasks.
   * This function only needs to be called once on page load,
   * as it using deferrer methods only
   */
  FormEngine.initializeEvents = function() {
    if (top.TYPO3 && typeof top.TYPO3.Backend !== 'undefined') {
      top.TYPO3.Backend.consumerScope.attach(FormEngine);
      $(window).on('unload', function() {
        top.TYPO3.Backend.consumerScope.detach(FormEngine);
      });
    }
    $(document).on('click', '.t3js-editform-close', (e: Event) => {
      e.preventDefault();
      FormEngine.preventExitIfNotSaved(
        FormEngine.preventExitIfNotSavedCallback
      );
    }).on('click', '.t3js-editform-view', (e: Event) => {
      e.preventDefault();
      FormEngine.previewAction(e, FormEngine.previewActionCallback);
    }).on('click', '.t3js-editform-new', (e: Event) => {
      e.preventDefault();
      FormEngine.newAction(e, FormEngine.newActionCallback);
    }).on('click', '.t3js-editform-duplicate', (e: Event) => {
      e.preventDefault();
      FormEngine.duplicateAction(e, FormEngine.duplicateActionCallback);
    }).on('click', '.t3js-editform-delete-record', (e: Event) => {
      e.preventDefault();
      FormEngine.deleteAction(e, FormEngine.deleteActionCallback);
    }).on('click', '.t3js-editform-submitButton', (event: JQueryEventObject) => {
      const $me = $(event.currentTarget),
        name = $me.data('name') || (event.currentTarget as HTMLInputElement).name,
        $elem = $('<input />').attr('type', 'hidden').attr('name', name).attr('value', '1');

      $me.parents('form').append($elem);
    }).on('change', '.t3-form-field-eval-null-checkbox input[type="checkbox"]', (e: JQueryEventObject) => {
      // Null checkboxes without placeholder click event handler
      $(e.currentTarget).closest('.t3js-formengine-field-item').toggleClass('disabled');
    }).on('change', '.t3js-form-field-eval-null-placeholder-checkbox input[type="checkbox"]', (e: JQueryEventObject) => {
      FormEngine.toggleCheckboxField($(e.currentTarget));
      FormEngineValidation.markFieldAsChanged($(e.currentTarget));
    }).on('change', function(event: Event) {
      $('.module-docheader-bar .btn').removeClass('disabled').prop('disabled', false);
    }).on('click', '.t3js-element-browser', function(e: Event) {
      e.preventDefault();
      e.stopPropagation();

      const $me = $(e.currentTarget);
      const mode = $me.data('mode');
      const params = $me.data('params');
      const entryPoint = $me.data('entryPoint');

      FormEngine.openPopupWindow(mode, params, entryPoint);
    }).on('click', '[data-formengine-field-change-event="click"]', (evt: Event) => {
      const items = JSON.parse((evt.currentTarget as HTMLElement).dataset.formengineFieldChangeItems);
      FormEngine.processOnFieldChange(items, evt);
    }).on('change', '[data-formengine-field-change-event="change"]', (evt: Event) => {
      const items = JSON.parse((evt.currentTarget as HTMLElement).dataset.formengineFieldChangeItems);
      FormEngine.processOnFieldChange(items, evt);
    });

    ((document as any).editform as HTMLFormElement).addEventListener('submit', function () {
      if (((document as any).editform as HTMLFormElement).closeDoc.value) {
        return;
      }

      const elements = [
        'button[form]',
        'button[name^="_save"]',
        'a[data-name^="_save"]',
        'button[name="CMD"][value^="save"]',
        'a[data-name="CMD"][data-value^="save"]',
      ].join(',');

      const button = document.querySelector(elements) as HTMLInputElement;
      if (button !== null) {
        button.disabled = true;

        Icons.getIcon('spinner-circle-dark', Icons.sizes.small).then(function (markup: string) {
          button.querySelector('.t3js-icon').outerHTML = markup;
        });
      }
    });

    window.addEventListener('message', FormEngine.handlePostMessage);
  };

  /**
   * @param {InteractionRequest} interactionRequest
   * @return {jQuery.Deferred}
   */
  FormEngine.consume = function(interactionRequest: TriggerRequest) {
    if (!interactionRequest) {
      throw new BackendExceptionModule.BackendException('No interaction request given', 1496589980);
    }
    const deferred = $.Deferred();
    if (interactionRequest.concernsTypes(FormEngine.consumeTypes)) {
      const outerMostRequest = interactionRequest.outerMostRequest;

      FormEngine.interactionRequestMap.attachFor(
        outerMostRequest,
        deferred
      );
      // resolve or reject deferreds with previous user choice
      if (outerMostRequest.isProcessed()) {
        handleConsumeResponse(
          outerMostRequest,
          outerMostRequest.getProcessedData().response
        );
        // show confirmation dialog
      } else if (FormEngine.hasChange()) {
        FormEngine.preventExitIfNotSaved(function(response: boolean) {
          outerMostRequest.setProcessedData(
            {response: response}
          );
          handleConsumeResponse(outerMostRequest, response);
        });
        // resolve directly
      } else {
        FormEngine.interactionRequestMap.resolveFor(outerMostRequest);
      }
    }
    return deferred;
  };

  FormEngine.handlePostMessage = function (e: MessageEvent) {
    if (!MessageUtility.MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:elementBrowser:elementAdded') {
      if (typeof e.data.fieldName === 'undefined') {
        throw 'fieldName not defined in message';
      }

      if (typeof e.data.value === 'undefined') {
        throw 'value not defined in message';
      }

      const label = e.data.label || e.data.value;
      const title = e.data.title || label;
      const exclusiveValues = e.data.exclusiveValues || '';

      FormEngine.setSelectOptionFromExternalSource(e.data.fieldName, e.data.value, label, title, exclusiveValues);
    }
  };

  /**
   * Initializes the remaining character views based on the fields' maxlength attribute
   */
  FormEngine.initializeRemainingCharacterViews = function() {
    // all fields with a "maxlength" attribute
    const $maxlengthElements = $('[maxlength]').not('.t3js-datetimepicker').not('.t3js-color-picker').not('.t3js-charcounter-initialized');
    $maxlengthElements.on('focus', (event: JQueryEventObject) => {
      const $field = $(event.currentTarget),
        $parent = $field.parents('.t3js-formengine-field-item:first'),
        maxlengthProperties = FormEngine.getCharacterCounterProperties($field);

      // append the counter only at focus to avoid cluttering the DOM
      let $wrapper = $parent.find('.t3js-charcounter-wrapper');
      if (!$wrapper.length) {
        $wrapper = $('<div>');
        $wrapper.addClass('t3js-charcounter-wrapper');
        $parent.append($wrapper);
      }
      $wrapper.append($('<div />', {'class': 't3js-charcounter'}).append(
        $('<span />', {'class': maxlengthProperties.labelClass}).text(TYPO3.lang['FormEngine.remainingCharacters'].replace('{0}', maxlengthProperties.remainingCharacters))
      ));
    }).on('blur', (event: JQueryEventObject) => {
      const $field = $(event.currentTarget),
        $parent = $field.parents('.t3js-formengine-field-item:first');
      $parent.find('.t3js-charcounter').remove();
    }).on('keyup', (event: JQueryEventObject) => {
      const $field = $(event.currentTarget),
        $parent = $field.parents('.t3js-formengine-field-item:first'),
        maxlengthProperties = FormEngine.getCharacterCounterProperties($field);

      // change class and value
      $parent.find('.t3js-charcounter span').removeClass().addClass(maxlengthProperties.labelClass).text(TYPO3.lang['FormEngine.remainingCharacters'].replace('{0}', maxlengthProperties.remainingCharacters))
    });
    $maxlengthElements.addClass('t3js-charcounter-initialized');
  };

  /**
   * Get the properties required for proper rendering of the character counter
   *
   * @param {Object} $field
   * @returns {{remainingCharacters: number, labelClass: string}}
   */
  FormEngine.getCharacterCounterProperties = function($field: JQuery): {remainingCharacters: number, labelClass: string} {
    const fieldText = $field.val(),
      maxlength = $field.attr('maxlength'),
      currentFieldLength = fieldText.length,
      numberOfLineBreaks = (fieldText.match(/\n/g) || []).length, // count line breaks
      // @ts-ignore
      remainingCharacters = maxlength - currentFieldLength - numberOfLineBreaks,
      threshold = 15; // hard limit of remaining characters when the label class changes
    let labelClass = '';

    if (remainingCharacters < threshold) {
      labelClass = 'badge-danger';
    } else if (remainingCharacters < threshold * 2) {
      labelClass = 'badge-warning';
    } else {
      labelClass = 'badge-info';
    }

    return {
      remainingCharacters: remainingCharacters,
      labelClass: 'badge ' + labelClass
    };
  };

  /**
   * Initializes the left character count needed to reach the minimum value based on the field's minlength attribute
   */
  FormEngine.initializeMinimumCharactersLeftViews = function () {
    // Helper method as replacement for jQuery "parents".
    const closest: Function = (el: ParentNode, fn: Function) => el && (fn(el) ? el : closest(el.parentNode, fn));

    const addOrUpdateCounter = (minCharacterCountLeft: string, event: Event) => {
      const parent = closest(event.currentTarget, (el: HTMLElement) => el.classList.contains('t3js-formengine-field-item'));
      const counter = parent.querySelector('.t3js-charcounter-min');
      const labelValue = TYPO3.lang['FormEngine.minCharactersLeft'].replace('{0}', minCharacterCountLeft);
      if (counter) {
        counter.querySelector('span').innerHTML = labelValue;
      } else {
        const counter = document.createElement('div');
        counter.classList.add('t3js-charcounter-min');
        const label = document.createElement('span');
        label.classList.add('badge', 'badge-danger');
        label.innerHTML = labelValue;
        counter.append(label);
        let wrapper = parent.querySelector('.t3js-charcounter-wrapper');
        if (!wrapper) {
          wrapper = document.createElement('div');
          wrapper.classList.add('t3js-charcounter-wrapper');
          parent.append(wrapper);
        }
        wrapper.prepend(counter);
      }
    };
    const removeCounter = (event: Event) => {
      const parent = closest(event.currentTarget, (el: HTMLElement) => el.classList.contains('t3js-formengine-field-item'));
      const counter = parent.querySelector('.t3js-charcounter-min');
      if (counter) {
        counter.remove();
      }
    };

    const minlengthElements = document.querySelectorAll('[minlength]:not(.t3js-datetimepicker):not(.t3js-charcounter-min-initialized)');
    minlengthElements.forEach((field: HTMLInputElement|HTMLTextAreaElement) => {
      field.addEventListener('focus', (event) => {
        const minCharacterCountLeft = FormEngine.getMinCharacterLeftCount(field);
        if (minCharacterCountLeft > 0) {
          addOrUpdateCounter(minCharacterCountLeft, event);
        }
      });

      field.addEventListener('blur', removeCounter);

      field.addEventListener('keyup', (event) => {
        const minCharacterCountLeft = FormEngine.getMinCharacterLeftCount(field);
        if (minCharacterCountLeft > 0) {
          addOrUpdateCounter(minCharacterCountLeft, event);
        } else {
          removeCounter(event);
        }
      });
    });
  };

  /**
   * Get the properties required for proper rendering of the character counter
   *
   * @param {HTMLElement} field
   * @returns number
   */
  FormEngine.getMinCharacterLeftCount = function (field: HTMLInputElement|HTMLTextAreaElement) {
    const text = field.value;
    const minlength = field.minLength;
    const currentFieldLength = text.length;

    // minLength doesn't care about empty fields.
    if (currentFieldLength === 0) {
      return 0;
    }

    const numberOfLineBreaks = (text.match(/\n/g) || []).length; // count line breaks
    const minimumCharactersLeft = minlength - currentFieldLength - numberOfLineBreaks;

    return minimumCharactersLeft;
  };

  /**
   * Initialize input / text field "null" checkbox CSS overlay if no placeholder is set.
   */
  FormEngine.initializeNullNoPlaceholderCheckboxes = function(): void {
    $('.t3-form-field-eval-null-checkbox').each(function(index: number, el: HTMLElement) {
      const $el = $(el);
      // Add disabled class to "t3js-formengine-field-item" if the null checkbox is NOT set,
      // This activates a CSS overlay "disabling" the input field and everything around.
      const $checkbox = $el.find('input[type="checkbox"]');
      const $fieldItem = $el.closest('.t3js-formengine-field-item');
      if (!$checkbox.attr('checked')) {
        $fieldItem.addClass('disabled');
      }
    });
  };

  /**
   * Initialize input / text field "null" checkbox placeholder / real field if placeholder is set.
   */
  FormEngine.initializeNullWithPlaceholderCheckboxes = function(): void {
    $('.t3js-form-field-eval-null-placeholder-checkbox').each((index: number, el: HTMLElement) => {
      FormEngine.toggleCheckboxField($(el).find('input[type="checkbox"]'), false);
    });
  };

  /**
   * Set initial state of both div's (one containing actual field, other containing placeholder field)
   * depending on whether checkbox is checked or not
   */
  FormEngine.toggleCheckboxField = function($checkbox: JQuery, triggerFocusWhenChecked: boolean = true): void {
    const $item = $checkbox.closest('.t3js-formengine-field-item');
    if ($checkbox.prop('checked')) {
      $item.find('.t3js-formengine-placeholder-placeholder').hide();
      $item.find('.t3js-formengine-placeholder-formfield').show();
      if (triggerFocusWhenChecked) {
        $item.find('.t3js-formengine-placeholder-formfield').find(':input').trigger('focus');
      }
    } else {
      $item.find('.t3js-formengine-placeholder-placeholder').show();
      $item.find('.t3js-formengine-placeholder-formfield').hide();
    }
  };

  /**
   * This is the main function that is called on page load, but also after elements are asynchronously
   * called e.g. after inline elements are loaded, or a new flexform section is added.
   * Use this function in your extension like this "TYPO3.FormEngine.initialize()"
   * if you add new fields dynamically.
   */
  FormEngine.reinitialize = function(): void {
    // Apply "close" button to all input / datetime fields
    const clearables = Array.from(document.querySelectorAll('.t3js-clearable'));
    if (clearables.length > 0) {
      import('@typo3/backend/input/clearable').then(function() {
        clearables.forEach(clearableField => (clearableField as any).clearable());
      });
    }

    FormEngine.initializeNullNoPlaceholderCheckboxes();
    FormEngine.initializeNullWithPlaceholderCheckboxes();
    FormEngine.initializeLocalizationStateSelector();
    FormEngine.initializeMinimumCharactersLeftViews();
    FormEngine.initializeRemainingCharacterViews();
  };

  /**
   * Disable the input field on load if localization state selector is set to "parent" or "source"
   */
  FormEngine.initializeLocalizationStateSelector = function(): void {
    $('.t3js-l10n-state-container').each((index: number, el: HTMLElement) => {
      const $el = $(el);
      const $input = $el.closest('.t3js-formengine-field-item').find('[data-formengine-input-name]');
      const currentState = $el.find('input[type="radio"]:checked').val();
      if (currentState === 'parent' || currentState === 'source') {
        $input.attr('disabled', 'disabled');
      }
    });
  };

  /**
   * @return {boolean}
   */
  FormEngine.hasChange = function(): boolean {
    const formElementChanges = $('form[name="' + FormEngine.formName + '"] .has-change').length > 0,
      inlineRecordChanges = $('[name^="data["].has-change').length > 0;
    return formElementChanges || inlineRecordChanges;
  };

  /**
   * @param {boolean} response
   */
  FormEngine.preventExitIfNotSavedCallback = function(response: boolean): void {
    FormEngine.closeDocument();
  };

  /**
   * Show modal to confirm following a clicked link to confirm leaving the document without saving
   *
   * @param {String} href
   * @returns {Boolean}
   */
  FormEngine.preventFollowLinkIfNotSaved = function(href: string): boolean {
    FormEngine.preventExitIfNotSaved(
      function () {
        window.location.href = href;
      }
    );
    return false;
  };

  /**
   * Show modal to confirm closing the document without saving.
   *
   * @param {Function} callback
   */
  FormEngine.preventExitIfNotSaved = function(callback: Function): void {
    callback = callback || FormEngine.preventExitIfNotSavedCallback;

    if (FormEngine.hasChange()) {
      const title = TYPO3.lang['label.confirm.close_without_save.title'] || 'Do you want to close without saving?';
      const content = TYPO3.lang['label.confirm.close_without_save.content'] || 'You currently have unsaved changes. Are you sure you want to discard these changes?';
      const $elem = $('<input />').attr('type', 'hidden').attr('name', '_saveandclosedok').attr('value', '1');
      const buttons: Array<{text: string, btnClass: string, name: string, active?: boolean}> = [
        {
          text: TYPO3.lang['buttons.confirm.close_without_save.no'] || 'No, I will continue editing',
          btnClass: 'btn-default',
          name: 'no'
        },
        {
          text: TYPO3.lang['buttons.confirm.close_without_save.yes'] || 'Yes, discard my changes',
          btnClass: 'btn-default',
          name: 'yes'
        }
      ];
      if ($('.has-error').length === 0) {
        buttons.push({
          text: TYPO3.lang['buttons.confirm.save_and_close'] || 'Save and close',
          btnClass: 'btn-warning',
          name: 'save',
          active: true
        });
      }

      const $modal = Modal.confirm(title, content, Severity.warning, buttons);
      $modal.on('button.clicked', function(e: Event) {
        if ((e.target as HTMLButtonElement).name === 'no') {
          Modal.dismiss();
        } else if ((e.target as HTMLButtonElement).name === 'yes') {
          Modal.dismiss();
          callback.call(null, true);
        } else if ((e.target as HTMLButtonElement).name === 'save') {
          $('form[name=' + FormEngine.formName + ']').append($elem);
          Modal.dismiss();
          FormEngine.saveDocument();
        }
      });
    } else {
      callback.call(null, true);
    }
  };

  /**
   * Show modal to confirm closing the document without saving
   */
  FormEngine.preventSaveIfHasErrors = function(): boolean {
    if ($('.has-error').length > 0) {
      const title = TYPO3.lang['label.alert.save_with_error.title'] || 'You have errors in your form!';
      const content = TYPO3.lang['label.alert.save_with_error.content'] || 'Please check the form, there is at least one error in your form.';
      const $modal = Modal.confirm(title, content, Severity.error, [
        {
          text: TYPO3.lang['buttons.alert.save_with_error.ok'] || 'OK',
          btnClass: 'btn-danger',
          name: 'ok'
        }
      ]);
      $modal.on('button.clicked', function(e: Event) {
        if ((e.target as HTMLButtonElement).name === 'ok') {
          Modal.dismiss();
        }
      });
      return false;
    }
    return true;
  };

  FormEngine.requestFormEngineUpdate = function(showConfirmation: boolean): void {
    if (showConfirmation) {
      const $modal = Modal.confirm(
        TYPO3.lang['FormEngine.refreshRequiredTitle'],
        TYPO3.lang['FormEngine.refreshRequiredContent']
      );

      $modal.on('button.clicked', function(e: Event) {
        if ((e.target as HTMLButtonElement).name === 'ok') {
          FormEngine.closeModalsRecursive();
          FormEngine.saveDocument();
        } else {
          Modal.dismiss();
        }
      });
    } else {
      FormEngine.saveDocument();
    }
  };

  /**
   * @param {OnFieldChangeItem[]} items
   * @param {Event|null|undefined} evt
   */
  FormEngine.processOnFieldChange = function(items: Array<OnFieldChangeItem>, evt?: Event|null) {
    items.forEach((item) => {
      const handler = onFieldChangeHandlers.get(item.name);
      if (handler instanceof Function) {
        handler.call(null, item.data || null, evt);
      }
    });
  };

  /**
   * @param {string} name
   * @param {Function} handler
   */
  FormEngine.registerOnFieldChangeHandler = function(name: string, handler: Function): void {
    if (onFieldChangeHandlers.has(name)) {
      console.warn('Handler for onFieldChange name `' + name + '` has been overridden.');
    }
    onFieldChangeHandlers.set(name, handler);
  }

  FormEngine.closeModalsRecursive = function() {
    if (typeof Modal.currentModal !== 'undefined' && Modal.currentModal !== null) {
      Modal.currentModal.on('hidden.bs.modal', function () {
        FormEngine.closeModalsRecursive(Modal.currentModal);
      });
      Modal.currentModal.trigger('modal-dismiss');
    }
  }

  /**
   * Preview action
   *
   * When there are changes:
   * Will take action based on local storage preset
   * If preset is not available, a modal will open
   *
   * @param {Event} event
   * @param {Function} callback
   */
  FormEngine.previewAction = function(event: Event, callback: Function): void {
    callback = callback || FormEngine.previewActionCallback;

    const previewUrl = (event.currentTarget as HTMLAnchorElement).href;
    const isNew = (event.target as HTMLAnchorElement).dataset.hasOwnProperty('isNew');
    const $actionElement = $('<input />').attr('type', 'hidden').attr('name', '_savedokview').attr('value', '1');
    if (FormEngine.hasChange()) {
      FormEngine.showPreviewModal(previewUrl, isNew, $actionElement, callback);
    } else {
      $('form[name=' + FormEngine.formName + ']').append($actionElement);
      window.open('', 'newTYPO3frontendWindow');
      ((document as any).editform as HTMLFormElement).submit();
    }
  };

  /**
   * The callback for the preview action
   *
   * @param {string} modalButtonName
   * @param {string} previewUrl
   * @param {element} $actionElement
   */
  FormEngine.previewActionCallback = function(modalButtonName: string, previewUrl: string, $actionElement: JQuery): void {
    Modal.dismiss();
    switch(modalButtonName) {
      case 'discard':
        const previewWin = window.open(previewUrl, 'newTYPO3frontendWindow');
        previewWin.focus();

        if (Utility.urlsPointToSameServerSideResource(previewWin.location.href, previewUrl)) {
          previewWin.location.reload();
        }
        break;
      case 'save':
        $('form[name=' + FormEngine.formName + ']').append($actionElement);
        window.open('', 'newTYPO3frontendWindow');
        FormEngine.saveDocument();
        break;
      default:
        break;
    }
  };

  /**
   * Show the preview modal
   *
   * @param {string} previewUrl
   * @param {bool} isNew
   * @param {element} $actionElement
   * @param {Function} callback
   */
  FormEngine.showPreviewModal = function(previewUrl: string, isNew: boolean, $actionElement: JQuery, callback: Function): void {
    const title = TYPO3.lang['label.confirm.view_record_changed.title'] || 'Do you want to save before viewing?';
    const modalCancelButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.view_record_changed.cancel'] || 'Cancel',
      btnClass: 'btn-default',
      name: 'cancel'
    };
    const modaldismissViewButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.view_record_changed.no-save'] || 'View without changes',
      btnClass: 'btn-info',
      name: 'discard'
    };
    const modalsaveViewButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.view_record_changed.save'] || 'Save changes and view',
      btnClass: 'btn-info',
      name: 'save',
      active: true
    };
    let modalButtons = [];
    let content = '';
    if (isNew) {
      modalButtons = [
        modalCancelButtonConfiguration,
        modalsaveViewButtonConfiguration
      ];
      content = (
        TYPO3.lang['label.confirm.view_record_changed.content.is-new-page']
        || 'You need to save your changes before viewing the page. Do you want to save and view them now?'
      );
    } else {
      modalButtons = [
        modalCancelButtonConfiguration,
        modaldismissViewButtonConfiguration,
        modalsaveViewButtonConfiguration
      ];
      content = (
        TYPO3.lang['label.confirm.view_record_changed.content']
        || 'You currently have unsaved changes. You can either discard these changes or save and view them.'
      )
    }
    const $modal = Modal.confirm(title, content, Severity.info, modalButtons);
    $modal.on('button.clicked', function (event: Event) {
      callback((event.target as HTMLButtonElement).name, previewUrl, $actionElement, $modal);
    });
  };

  /**
   * New action
   *
   * When there are changes:
   * Will take action based on local storage preset
   * If preset is not available, a modal will open
   *
   * @param {Event} event
   * @param {Function} callback
   */
  FormEngine.newAction = function(event: Event, callback: Function): void {
    callback = callback || FormEngine.newActionCallback;

    const $actionElement = $('<input />').attr('type', 'hidden').attr('name', '_savedoknew').attr('value', '1');
    const isNew = (event.target as HTMLElement).dataset.hasOwnProperty('isNew');
    if (FormEngine.hasChange()) {
      FormEngine.showNewModal(isNew, $actionElement, callback);
    } else {
      $('form[name=' + FormEngine.formName + ']').append($actionElement);
      ((document as any).editform as HTMLFormElement).submit();
    }
  };

  /**
   * The callback for the preview action
   *
   * @param {string} modalButtonName
   * @param {element} $actionElement
   */
  FormEngine.newActionCallback = function(modalButtonName: string, $actionElement: JQuery): void {
    const $form = $('form[name=' + FormEngine.formName + ']');
    Modal.dismiss();
    switch(modalButtonName) {
      case 'no':
        $form.append($actionElement);
        ((document as any).editform as HTMLFormElement).submit();
        break;
      case 'yes':
        $form.append($actionElement);
        FormEngine.saveDocument();
        break;
      default:
        break;
    }
  };

  /**
   * Show the new modal
   *
   * @param {bool} isNew
   * @param {element} $actionElement
   * @param {Function} callback
   */
  FormEngine.showNewModal = function(isNew: boolean, $actionElement: JQuery, callback: Function): void {
    const title = TYPO3.lang['label.confirm.new_record_changed.title'] || 'Do you want to save before adding?';
    const content = (
      TYPO3.lang['label.confirm.new_record_changed.content']
      || 'You need to save your changes before creating a new record. Do you want to save and create now?'
    );
    let modalButtons = [];
    const modalCancelButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.new_record_changed.cancel'] || 'Cancel',
      btnClass: 'btn-default',
      name: 'cancel'
    };
    const modalNoButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.new_record_changed.no'] || 'No, just add',
      btnClass: 'btn-default',
      name: 'no'
    };
    const modalYesButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.new_record_changed.yes'] || 'Yes, save and create now',
      btnClass: 'btn-info',
      name: 'yes',
      active: true
    };
    if (isNew) {
      modalButtons = [
        modalCancelButtonConfiguration,
        modalYesButtonConfiguration
      ];
    } else {
      modalButtons = [
        modalCancelButtonConfiguration,
        modalNoButtonConfiguration,
        modalYesButtonConfiguration
      ];
    }
    const $modal = Modal.confirm(title, content, Severity.info, modalButtons);
    $modal.on('button.clicked', function (event: Event) {
      callback((event.target as HTMLButtonElement).name, $actionElement);
    });
  };

  /**
   * Duplicate action
   *
   * When there are changes:
   * Will take action based on local storage preset
   * If preset is not available, a modal will open
   *
   * @param {Event} event
   * @param {Function} callback
   */
  FormEngine.duplicateAction = function(event: Event, callback: Function): void {
    callback = callback || FormEngine.duplicateActionCallback;

    const $actionElement = $('<input />').attr('type', 'hidden').attr('name', '_duplicatedoc').attr('value', '1');
    const isNew = (event.target as HTMLElement).dataset.hasOwnProperty('isNew');
    if (FormEngine.hasChange()) {
      FormEngine.showDuplicateModal(isNew, $actionElement, callback);
    } else {
      $('form[name=' + FormEngine.formName + ']').append($actionElement);
      ((document as any).editform as HTMLFormElement).submit();
    }
  };

  /**
   * The callback for the duplicate action
   *
   * @param {string} modalButtonName
   * @param {element} $actionElement
   */
  FormEngine.duplicateActionCallback = function(modalButtonName: string, $actionElement: JQuery): void {
    const $form = $('form[name=' + FormEngine.formName + ']');
    Modal.dismiss();
    switch(modalButtonName) {
      case 'no':
        $form.append($actionElement);
        ((document as any).editform as HTMLFormElement).submit();
        break;
      case 'yes':
        $form.append($actionElement);
        FormEngine.saveDocument();
        break;
      default:
        break;
    }
  };

  /**
   * Show the duplicate modal
   *
   * @param {bool} isNew
   * @param {element} $actionElement
   * @param {Function} callback
   */
  FormEngine.showDuplicateModal = function(isNew: boolean, $actionElement: JQuery, callback: Function): void {
    const title = TYPO3.lang['label.confirm.duplicate_record_changed.title'] || 'Do you want to save before duplicating this record?';
    const content = (
      TYPO3.lang['label.confirm.duplicate_record_changed.content']
      || 'You currently have unsaved changes. Do you want to save your changes before duplicating this record?'
    );
    let modalButtons = [];
    const modalCancelButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.duplicate_record_changed.cancel'] || 'Cancel',
      btnClass: 'btn-default',
      name: 'cancel'
    };
    const modalDismissDuplicateButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.duplicate_record_changed.no'] || 'No, just duplicate the original',
      btnClass: 'btn-default',
      name: 'no'
    };
    const modalSaveDuplicateButtonConfiguration = {
      text: TYPO3.lang['buttons.confirm.duplicate_record_changed.yes'] || 'Yes, save and duplicate this record',
      btnClass: 'btn-info',
      name: 'yes',
      active: true
    };
    if (isNew) {
      modalButtons = [
        modalCancelButtonConfiguration,
        modalSaveDuplicateButtonConfiguration
      ];
    } else {
      modalButtons = [
        modalCancelButtonConfiguration,
        modalDismissDuplicateButtonConfiguration,
        modalSaveDuplicateButtonConfiguration
      ];
    }
    const $modal = Modal.confirm(title, content, Severity.info, modalButtons);
    $modal.on('button.clicked', function (event: Event) {
      callback((event.target as HTMLButtonElement).name, $actionElement);
    });
  };

  /**
   * Delete action
   *
   * When there are changes:
   * Will take action based on local storage preset
   * If preset is not available, a modal will open
   *
   * @param {Event} event
   * @param {Function} callback
   */
  FormEngine.deleteAction = function(event: Event, callback: Function): void {
    callback = callback || FormEngine.deleteActionCallback;

    const $anchorElement = $(event.target);

    FormEngine.showDeleteModal($anchorElement, callback);
  };

  /**
   * The callback for the delete action
   *
   * @param {string} modalButtonName
   * @param {element} $anchorElement
   */
  FormEngine.deleteActionCallback = function(modalButtonName: string, $anchorElement: JQuery): void {
    Modal.dismiss();
    if (modalButtonName === 'yes') {
      FormEngine.invokeRecordDeletion($anchorElement);
    }
  };

  /**
   * Show the delete modal
   *
   * @param {element} $anchorElement
   * @param {Function} callback
   */
  FormEngine.showDeleteModal = function($anchorElement: JQuery, callback: Function): void {
    const title = TYPO3.lang['label.confirm.delete_record.title'] || 'Delete this record?';
    let content = (TYPO3.lang['label.confirm.delete_record.content'] || 'Are you sure you want to delete the record \'%s\'?').replace('%s', $anchorElement.data('record-info'));

    if ($anchorElement.data('reference-count-message')) {
      content += '\n' + $anchorElement.data('reference-count-message');
    }

    if ($anchorElement.data('translation-count-message')) {
      content += '\n' + $anchorElement.data('translation-count-message');
    }

    const $modal = Modal.confirm(title, content, Severity.warning, [
      {
        text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
        btnClass: 'btn-default',
        name: 'no'
      },
      {
        text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this record',
        btnClass: 'btn-warning',
        name: 'yes',
        active: true
      }
    ]);
    $modal.on('button.clicked', function (event: Event) {
      callback((event.target as HTMLButtonElement).name, $anchorElement);
    });
  };

  /**
   * In case the given option is a child of a disabled optGroup, enable the optGroup
   */
  FormEngine.enableOptGroup = function (option: HTMLOptionElement): void {
    const optGroup = <HTMLOptGroupElement>option.parentElement;
    if (optGroup instanceof HTMLOptGroupElement && optGroup.querySelectorAll('option:not([hidden]):not([disabled]):not(.hidden)').length) {
      optGroup.hidden = false;
      optGroup.disabled = false;
      optGroup.classList.remove('hidden');
    }
  }

  /**
   * Close current open document
   */
  FormEngine.closeDocument = function(): void {
    ((document as any).editform as HTMLFormElement).closeDoc.value = 1;

    FormEngine.dispatchSubmitEvent();
    ((document as any).editform as HTMLFormElement).submit();
  };

  FormEngine.saveDocument = function(): void {
    ((document as any).editform as HTMLFormElement).doSave.value = 1;

    FormEngine.dispatchSubmitEvent();
    ((document as any).editform as HTMLFormElement).submit();
  };

  /**
   * Dispatches the "submit" event to the form. This is necessary if .submit() is called directly.
   */
  FormEngine.dispatchSubmitEvent = function(): void {
    const submitEvent = document.createEvent('Event');
    submitEvent.initEvent('submit', false, true);
    ((document as any).editform as HTMLFormElement).dispatchEvent(submitEvent);
  };

  /**
   * Main init function called from outside
   *
   * Sets some options and registers the DOMready handler to initialize further things
   *
   * @param {String} browserUrl
   */
  FormEngine.initialize = function(browserUrl: string): void {
    FormEngine.browserUrl = browserUrl;

    $(function() {
      FormEngine.initializeEvents();
      FormEngine.Validation.initialize();
      FormEngine.reinitialize();
      $('#t3js-ui-block').remove();
    });
  };

  FormEngine.invokeRecordDeletion = function ($anchorElement: JQuery) {
    window.location.href = $anchorElement.attr('href');
  };

  // load required modules to hook in the post initialize function
  if (undefined !== TYPO3.settings.RequireJS && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine']) {
    $.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine'], function(pos: number, moduleName: string) {
      window.require([moduleName]);
    });
  }

  // make the form engine object publicly visible for other objects in the TYPO3 namespace
  TYPO3.FormEngine = FormEngine;

  // return the object in the global space
  return FormEngine;
})();
