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

// add legacy functions to be accessible in the global scope
var setFormValueOpenBrowser,
  setFormValueFromBrowseWin,
  setHiddenFromList,
  setFormValueManipulate,
  setFormValue_getFObj;

/**
 * Module: TYPO3/CMS/Backend/FormEngine
 */
define(['jquery',
  'TYPO3/CMS/Backend/FormEngineValidation',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity'
], function($, FormEngineValidation, Modal, Severity) {

  /**
   *
   * @type {{Validation: object, formName: *, openedPopupWindow: window, legacyFieldChangedCb: Function, browserUrl: string}}
   * @exports TYPO3/CMS/Backend/FormEngine
   */
  var FormEngine = {
    Validation: FormEngineValidation,
    formName: TYPO3.settings.FormEngine.formName,
    openedPopupWindow: null,
    legacyFieldChangedCb: function() {
      !$.isFunction(TYPO3.settings.FormEngine.legacyFieldChangedCb) || TYPO3.settings.FormEngine.legacyFieldChangedCb();
    },
    browserUrl: ''
  };

  // functions to connect the db/file browser with this document and the formfields on it!

  /**
   * opens a popup window with the element browser (browser.php)
   *
   * @param {String} mode can be "db" or "file"
   * @param {String} params additional params for the browser window
   * @param {Number} width width of the window
   * @param {Number} height height of the window
   */
  FormEngine.openPopupWindow = setFormValueOpenBrowser = function(mode, params, width, height) {
    var url = FormEngine.browserUrl + '&mode=' + mode + '&bparams=' + params;
    width = width ? width : TYPO3.settings.Popup.PopupWindow.width;
    height = height ? height : TYPO3.settings.Popup.PopupWindow.height;
    FormEngine.openedPopupWindow = window.open(url, 'Typo3WinBrowser', 'height=' + height + ',width=' + width + ',status=0,menubar=0,resizable=1,scrollbars=1');
    FormEngine.openedPopupWindow.focus();
  };


  /**
   * properly fills the select field from the popup window (element browser, link browser)
   * or from a multi-select (two selects side-by-side)
   * previously known as "setFormValueFromBrowseWin"
   *
   * @param {String} fieldName Formerly known as "fName" name of the field, like [tt_content][2387][header]
   * @param {(String|Number)} value The value to fill in (could be an integer)
   * @param {String} label The visible name in the selector
   * @param {String} title The title when hovering over it
   * @param {String} exclusiveValues If the select field has exclusive options that are not combine-able
   * @param {$} $optionEl The jQuery object of the selected <option> tag
   */
  FormEngine.setSelectOptionFromExternalSource = setFormValueFromBrowseWin = function(fieldName, value, label, title, exclusiveValues, $optionEl) {
    exclusiveValues = String(exclusiveValues);

    var $fieldEl,
      $originalFieldEl,
      isMultiple = false,
      isList = false;

    $originalFieldEl = $fieldEl = FormEngine.getFieldElement(fieldName);

    if ($originalFieldEl.length === 0 || value === '--div--') {
      return;
    }

    // Check if the form object has a "_list" element
    // The "_list" element exists for multiple selection select types
    var $listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true);
    if ($listFieldEl.length > 0) {
      $fieldEl = $listFieldEl;
      isMultiple = ($fieldEl.prop('multiple') && $fieldEl.prop('size') != '1');
      isList = true;
    }

    // clear field before adding value, if configured so (maxitems==1)
    // @todo: clean this code
    if (typeof TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[fieldName] !== 'undefined') {
      var clearSettings = TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[fieldName];
      $fieldEl.empty();

      // Clear the upload field
      // @todo: Investigate whether we either need to fix this code or we can drop it.
      var filesContainer = document.getElementById(clearSettings.itemFormElID_file);
      if (filesContainer) {
        filesContainer.innerHTML = filesContainer.innerHTML;
      }
    }

    if (isMultiple || isList) {
      // If multiple values are not allowed, clear anything that is in the control already
      if (!isMultiple) {
        $fieldEl.empty();
      }

      // Clear elements if exclusive values are found
      if (exclusiveValues) {
        var reenableOptions = false;

        var m = new RegExp('(^|,)' + value + '($|,)');
        // the new value is exclusive => remove all existing values
        if (exclusiveValues.match(m)) {
          $fieldEl.empty();
          reenableOptions = true;
        } else if ($fieldEl.find('option').length == 1) {
          // there is an old value and it was exclusive => it has to be removed
          m = new RegExp("(^|,)" + $fieldEl.find('option').prop('value') + "($|,)");
          if (exclusiveValues.match(m)) {
            $fieldEl.empty();
            reenableOptions = true;
          }
        }

        if (reenableOptions && typeof $optionEl !== 'undefined') {
          $optionEl.closest('select').find('[disabled]').removeClass('hidden').prop('disabled', false)
        }
      }

      // Inserting the new element
      var addNewValue = true;

      // check if there is a "_mul" field (a field on the right) and if the field was already added
      var $multipleFieldEl = FormEngine.getFieldElement(fieldName, '_mul', true);
      if ($multipleFieldEl.length == 0 || $multipleFieldEl.val() == 0) {
        $fieldEl.find('option').each(function(k, optionEl) {
          if ($(optionEl).prop('value') == value) {
            addNewValue = false;
            return false;
          }
        });

        if (addNewValue && typeof $optionEl !== 'undefined') {
          $optionEl.addClass('hidden').prop('disabled', true);
        }
      }

      // element can be added
      if (addNewValue) {
        // finally add the option
        var $option = $('<option></option>');
        $option.attr({value: value, title: title}).text(label);
        $option.appendTo($fieldEl);

        // set the hidden field
        FormEngine.updateHiddenFieldValueFromSelect($fieldEl, $originalFieldEl);

        // execute the phpcode from $FormEngine->TBE_EDITOR_fieldChanged_func
        FormEngine.legacyFieldChangedCb();
      }

    } else {

      // The incoming value consists of the table name, an underscore and the uid
      // or just the uid
      // For a single selection field we need only the uid, so we extract it
      var pattern = /_(\\d+)$/
        , result = value.toString().match(pattern);

      if (result != null) {
        value = result[1];
      }

      // Change the selected value
      $fieldEl.val(value);
    }
    if (typeof FormEngine.Validation !== 'undefined' && typeof FormEngine.Validation.validate === 'function') {
      FormEngine.Validation.validate();
    }
  };

  /**
   * sets the value of the hidden field, from the select list, always executed after the select field was updated
   * previously known as global function setHiddenFromList()
   *
   * @param {HTMLElement} selectFieldEl the select field
   * @param {HTMLElement} originalFieldEl the hidden form field
   */
  FormEngine.updateHiddenFieldValueFromSelect = setHiddenFromList = function(selectFieldEl, originalFieldEl) {
    var selectedValues = [];
    $(selectFieldEl).find('option').each(function() {
      selectedValues.push($(this).prop('value'));
    });

    // make a comma separated list, if it is a multi-select
    // set the values to the final hidden field
    $(originalFieldEl).val(selectedValues.join(','));
  };

  /**
   * legacy function, can be removed once this function is not in use anymore
   *
   * @param {String} fName
   * @param {String} type
   * @param {Number} maxLength
   */
  setFormValueManipulate = function(fName, type, maxLength) {
    var $formEl = FormEngine.getFormElement(fName);
    if ($formEl.length > 0) {
      var formObj = $formEl.get(0);
      var localArray_V = [];
      var localArray_L = [];
      var localArray_S = [];
      var localArray_T = [];
      var fObjSel = formObj[fName + '_list'];
      var l = fObjSel.length;
      var c = 0;
      var a;

      if (type === 'RemoveFirstIfFull') {
        if (maxLength == 1) {
          for (a = 1; a < l; a++) {
            if (fObjSel.options[a].selected != 1) {
              localArray_V[c] = fObjSel.options[a].value;
              localArray_L[c] = fObjSel.options[a].text;
              localArray_S[c] = 0;
              localArray_T[c] = fObjSel.options[a].title;
              c++;
            }
          }
        } else {
          return;
        }
      }

      if ((type === "Remove" && fObjSel.size > 1) || type === "Top" || type === "Bottom") {
        if (type === "Top") {
          for (a = 0; a < l; a++) {
            if (fObjSel.options[a].selected == 1) {
              localArray_V[c] = fObjSel.options[a].value;
              localArray_L[c] = fObjSel.options[a].text;
              localArray_S[c] = 1;
              localArray_T[c] = fObjSel.options[a].title;
              c++;
            }
          }
        }
        for (a = 0; a < l; a++) {
          if (fObjSel.options[a].selected != 1) {
            localArray_V[c] = fObjSel.options[a].value;
            localArray_L[c] = fObjSel.options[a].text;
            localArray_S[c] = 0;
            localArray_T[c] = fObjSel.options[a].title;
            c++;
          }
        }
        if (type === "Bottom") {
          for (a = 0; a < l; a++) {
            if (fObjSel.options[a].selected == 1) {
              localArray_V[c] = fObjSel.options[a].value;
              localArray_L[c] = fObjSel.options[a].text;
              localArray_S[c] = 1;
              localArray_T[c] = fObjSel.options[a].title;
              c++;
            }
          }
        }
      }
      if (type === "Down") {
        var tC = 0;
        var tA = [];
        var aa = 0;

        for (a = 0; a < l; a++) {
          if (fObjSel.options[a].selected != 1) {
            // Add non-selected element:
            localArray_V[c] = fObjSel.options[a].value;
            localArray_L[c] = fObjSel.options[a].text;
            localArray_S[c] = 0;
            localArray_T[c] = fObjSel.options[a].title;
            c++;

            // Transfer any accumulated and reset:
            if (tA.length > 0) {
              for (aa = 0; aa < tA.length; aa++) {
                localArray_V[c] = fObjSel.options[tA[aa]].value;
                localArray_L[c] = fObjSel.options[tA[aa]].text;
                localArray_S[c] = 1;
                localArray_T[c] = fObjSel.options[tA[aa]].title;
                c++;
              }

              tC = 0;
              tA = [];
            }
          } else {
            tA[tC] = a;
            tC++;
          }
        }
        // Transfer any remaining:
        if (tA.length > 0) {
          for (aa = 0; aa < tA.length; aa++) {
            localArray_V[c] = fObjSel.options[tA[aa]].value;
            localArray_L[c] = fObjSel.options[tA[aa]].text;
            localArray_S[c] = 1;
            localArray_T[c] = fObjSel.options[tA[aa]].title;
            c++;
          }
        }
      }
      if (type === "Up") {
        var tC = 0;
        var tA = [];
        var aa = 0;
        c = l - 1;

        for (a = l - 1; a >= 0; a--) {
          if (fObjSel.options[a].selected != 1) {

            // Add non-selected element:
            localArray_V[c] = fObjSel.options[a].value;
            localArray_L[c] = fObjSel.options[a].text;
            localArray_S[c] = 0;
            localArray_T[c] = fObjSel.options[a].title;
            c--;

            // Transfer any accumulated and reset:
            if (tA.length > 0) {
              for (aa = 0; aa < tA.length; aa++) {
                localArray_V[c] = fObjSel.options[tA[aa]].value;
                localArray_L[c] = fObjSel.options[tA[aa]].text;
                localArray_S[c] = 1;
                localArray_T[c] = fObjSel.options[tA[aa]].title;
                c--;
              }

              tC = 0;
              tA = [];
            }
          } else {
            tA[tC] = a;
            tC++;
          }
        }
        // Transfer any remaining:
        if (tA.length > 0) {
          for (aa = 0; aa < tA.length; aa++) {
            localArray_V[c] = fObjSel.options[tA[aa]].value;
            localArray_L[c] = fObjSel.options[tA[aa]].text;
            localArray_S[c] = 1;
            localArray_T[c] = fObjSel.options[tA[aa]].title;
            c--;
          }
        }
        c = l;	// Restore length value in "c"
      }

      // Transfer items in temporary storage to list object:
      fObjSel.length = c;
      for (a = 0; a < c; a++) {
        fObjSel.options[a].value = localArray_V[a];
        fObjSel.options[a].text = localArray_L[a];
        fObjSel.options[a].selected = localArray_S[a];
        fObjSel.options[a].title = localArray_T[a];
      }
      FormEngine.updateHiddenFieldValueFromSelect(fObjSel, formObj[fName]);

      FormEngine.legacyFieldChangedCb();
    }
  };


  /**
   * Legacy function
   * returns the DOM object for the given form name of the current form,
   * but only if the given field name is valid, legacy function, use "getFormElement" instead
   *
   * @param {String} fieldName the name of the field name
   * @returns {*|HTMLElement}
   */
  setFormValue_getFObj = function(fieldName) {
    var $formEl = FormEngine.getFormElement(fieldName);
    if ($formEl.length > 0) {
      // return the DOM element of the form object
      return $formEl.get(0);
    }
    return null;
  };

  /**
   * returns a jQuery object for the given form name of the current form,
   * if the parameter "fieldName" is given, then the form element is only returned if the field name is available
   * the latter behaviour mirrors the one of the function "setFormValue_getFObj"
   *
   * @param {String} fieldName the field name to check for, optional
   * @returns {*|HTMLElement}
   */
  FormEngine.getFormElement = function(fieldName) {
    var $formEl = $('form[name="' + FormEngine.formName + '"]:first');
    if (fieldName) {
      var $fieldEl = FormEngine.getFieldElement(fieldName)
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
  FormEngine.getFieldElement = function(fieldName, appendix, noFallback) {
    var $formEl = FormEngine.getFormElement();

    // if an appendix is set, return the field with the appendix (like _mul or _list)
    if (appendix) {
      var $fieldEl;
      switch (appendix) {
        case '_list':
          $fieldEl = $(':input.tceforms-multiselect[data-formengine-input-name="' + fieldName + '"]', $formEl);
          break;
        case '_avail':
          $fieldEl = $(':input[data-relatedfieldname="' + fieldName + '"]', $formEl);
          break;
        case '_mul':
        case '_hr':
          $fieldEl = $(':input[type=hidden][data-formengine-input-name="' + fieldName + '"]', $formEl);
          break;
      }
      if (($fieldEl && $fieldEl.length > 0) || noFallback === true) {
        return $fieldEl;
      }
    }

    return $(':input[name="' + fieldName + '"]', $formEl);
  };


  /**************************************************
   * manipulate existing options in a select field
   **************************************************/

  /**
   * Moves currently selected options from a select field to the very top,
   * can be multiple entries as well
   *
   * @param {Object} $fieldEl a jQuery object, containing the select field
   */
  FormEngine.moveOptionToTop = function($fieldEl) {
    // remove the selected options
    var selectedOptions = $fieldEl.find(':selected').detach();
    // and add them on first position again
    $fieldEl.prepend(selectedOptions);
  };


  /**
   * moves currently selected options from a select field up by one position,
   * can be multiple entries as well
   *
   * @param {Object} $fieldEl a jQuery object, containing the select field
   */
  FormEngine.moveOptionUp = function($fieldEl) {
    // remove the selected options and add it before the previous sibling
    $.each($fieldEl.find(':selected'), function(k, optionEl) {
      var $optionEl = $(optionEl)
        , $optionBefore = $optionEl.prev();

      // stop if first option to move is already the first one
      if (k == 0 && $optionBefore.length === 0) {
        return false;
      }

      $optionBefore.before($optionEl.detach());
    });
  };


  /**
   * moves currently selected options from a select field down one position,
   * can be multiple entries as well
   *
   * @param {Object} $fieldEl a jQuery object, containing the select field
   */
  FormEngine.moveOptionDown = function($fieldEl) {
    // remove the selected options and add it after the next sibling
    // however, this time, we need to go from the last to the first
    var selectedOptions = $fieldEl.find(':selected');
    selectedOptions = $.makeArray(selectedOptions);
    selectedOptions.reverse();
    $.each(selectedOptions, function(k, optionEl) {
      var $optionEl = $(optionEl)
        , $optionAfter = $optionEl.next();

      // stop if first option to move is already the last one
      if (k == 0 && $optionAfter.length === 0) {
        return false;
      }

      $optionAfter.after($optionEl.detach());
    });
  };


  /**
   * moves currently selected options from a select field as the very last entries
   *
   * @param {Object} $fieldEl a jQuery object, containing the select field
   */
  FormEngine.moveOptionToBottom = function($fieldEl) {
    // remove the selected options
    var selectedOptions = $fieldEl.find(':selected').detach();
    // and add them on last position again
    $fieldEl.append(selectedOptions);
  };

  /**
   * removes currently selected options from a select field
   *
   * @param {Object} $fieldEl a jQuery object, containing the select field
   * @param {Object} $availableFieldEl a jQuery object, containing all available value
   */
  FormEngine.removeOption = function($fieldEl, $availableFieldEl) {
    var $selected = $fieldEl.find(':selected');

    $selected.each(function() {
      $availableFieldEl
        .find('option[value="' + $.escapeSelector($(this).attr('value')) + '"]')
        .removeClass('hidden')
        .prop('disabled', false);
    });

    // remove the selected options
    $selected.remove();
  };


  /**
   * Initialize events for all form engine relevant tasks.
   * This function only needs to be called once on page load,
   * as it using deferrer methods only
   */
  FormEngine.initializeEvents = function() {
    $(document).on('click', '.t3js-btn-moveoption-top, .t3js-btn-moveoption-up, .t3js-btn-moveoption-down, .t3js-btn-moveoption-bottom, .t3js-btn-removeoption', function(evt) {
      evt.preventDefault();

      // track the arrows "Up", "Down", "Clear" etc in multi-select boxes
      var $el = $(this)
        , fieldName = $el.data('fieldname')
        , $listFieldEl = FormEngine.getFieldElement(fieldName, '_list');

      if ($listFieldEl.length > 0) {

        if ($el.hasClass('t3js-btn-moveoption-top')) {
          FormEngine.moveOptionToTop($listFieldEl);
        } else if ($el.hasClass('t3js-btn-moveoption-up')) {
          FormEngine.moveOptionUp($listFieldEl);
        } else if ($el.hasClass('t3js-btn-moveoption-down')) {
          FormEngine.moveOptionDown($listFieldEl);
        } else if ($el.hasClass('t3js-btn-moveoption-bottom')) {
          FormEngine.moveOptionToBottom($listFieldEl);
        } else if ($el.hasClass('t3js-btn-removeoption')) {
          var $availableFieldEl = FormEngine.getFieldElement(fieldName, '_avail');
          FormEngine.removeOption($listFieldEl, $availableFieldEl);
        }

        // make sure to update the hidden field value when modifying the select value
        FormEngine.updateHiddenFieldValueFromSelect($listFieldEl, FormEngine.getFieldElement(fieldName));
        FormEngine.legacyFieldChangedCb();
        if (typeof FormEngine.Validation !== 'undefined' && typeof FormEngine.Validation.validate === 'function') {
          FormEngine.Validation.validate();
        }
      }
    }).on('click', '.t3js-formengine-select-itemstoselect', function(evt) {
      // in multi-select environments with two (e.g. "Access"), on click the item from the right should go to the left
      var $el = $(this)
        , fieldName = $el.data('relatedfieldname')
        , exclusiveValues = $el.data('exclusivevalues');

      if (fieldName) {
        // try to add each selected field to the "left" select field
        $el.find(':selected').each(function() {
          var $optionEl = $(this);
          FormEngine.setSelectOptionFromExternalSource(fieldName, $optionEl.prop('value'), $optionEl.text(), $optionEl.prop('title'), exclusiveValues, $optionEl);
        });
      }
    }).on('click', '.t3js-editform-close', function(e) {
      e.preventDefault();
      FormEngine.preventExitIfNotSaved();
    }).on('click', '.t3js-editform-delete-record', function(e) {
      e.preventDefault();
      var title = TYPO3.lang['label.confirm.delete_record.title'] || 'Delete this record?';
      var content = TYPO3.lang['label.confirm.delete_record.content'] || 'Are you sure you want to delete this record?';
      var $anchorElement = $(this);
      var $modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'no'
        },
        {
          text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this record',
          btnClass: 'btn-warning',
          name: 'yes'
        }
      ]);
      $modal.on('button.clicked', function(e) {
        if (e.target.name === 'no') {
          Modal.dismiss();
        } else if (e.target.name === 'yes') {
          deleteRecord($anchorElement.data('table'), $anchorElement.data('uid'), $anchorElement.data('return-url'));
          Modal.dismiss();
        }
      });
    }).on('click', '.t3js-editform-delete-inline-record', function(e) {
      e.preventDefault();
      var title = TYPO3.lang['label.confirm.delete_record.title'] || 'Delete this record?';
      var content = TYPO3.lang['label.confirm.delete_record.content'] || 'Are you sure you want to delete this record?';
      var $anchorElement = $(this);
      var $modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'no'
        },
        {
          text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this record',
          btnClass: 'btn-warning',
          name: 'yes'
        }
      ]);
      $modal.on('button.clicked', function(e) {
        if (e.target.name === 'no') {
          Modal.dismiss();
        } else if (e.target.name === 'yes') {
          var objectId = $anchorElement.data('objectid');
          inline.deleteRecord(objectId);
          Modal.dismiss();
        }
      });
    }).on('click', '.t3js-editform-submitButton', function(event) {
      // remember the clicked submit button. we need to know that in TBE_EDITOR.submitForm();
      var $me = $(this),
        name = $me.data('name') || this.name,
        $elem = $('<input />').attr('type', 'hidden').attr('name', name).attr('value', '1');

      $me.parents('form').append($elem);
    }).on('change', '.t3-form-field-eval-null-checkbox input[type="checkbox"]', function(e) {
      // Null checkboxes without placeholder click event handler
      $(this).closest('.t3js-formengine-field-item').toggleClass('disabled');
    }).on('change', '.t3js-form-field-eval-null-placeholder-checkbox input[type="checkbox"]', function(e) {
      FormEngine.toggleCheckboxField($(this));
    }).on('change', '.t3js-l10n-state-container input[type=radio]', function(event) {
      // Change handler for "l10n_state" field changes
      var $me = $(this);
      var $input = $me.closest('.t3js-formengine-field-item').find('[data-formengine-input-name]');

      if ($input.length > 0) {
        var lastState = $input.data('last-l10n-state') || false,
          currentState = $(this).val();

        if (lastState && currentState === lastState) {
          return;
        }

        if (currentState === 'custom') {
          if (lastState) {
            $(this).attr('data-original-language-value', $input.val());
          }
          $input.attr('disabled', false);
        } else {
          if (lastState === 'custom') {
            $(this).closest('.t3js-l10n-state-container').find('.t3js-l10n-state-custom').attr('data-original-language-value', $input.val());
          }
          $input.attr('disabled', 'disabled');
        }

        $input.val($(this).attr('data-original-language-value')).trigger('change');
        $input.data('last-l10n-state', $(this).val());
      }
    }).on('formengine.dp.change', function(event, $field) {
      FormEngine.Validation.validate();
      FormEngine.Validation.markFieldAsChanged($field);
    });
  };

  /**
   * Initializes the remaining character views based on the fields' maxlength attribute
   */
  FormEngine.initializeRemainingCharacterViews = function() {
    // all fields with a "maxlength" attribute
    var $maxlengthElements = $('[maxlength]').not('.t3js-datetimepicker').not('.t3js-charcounter-initialized');
    $maxlengthElements.on('focus', function(e) {
      var $field = $(this),
        $parent = $field.parents('.t3js-formengine-field-item:first'),
        maxlengthProperties = FormEngine.getCharacterCounterProperties($field);

      // append the counter only at focus to avoid cluttering the DOM
      $parent.append($('<div />', {'class': 't3js-charcounter'}).append(
        $('<span />', {'class': maxlengthProperties.labelClass}).text(TYPO3.lang['FormEngine.remainingCharacters'].replace('{0}', maxlengthProperties.remainingCharacters))
      ));
    }).on('blur', function() {
      var $field = $(this),
        $parent = $field.parents('.t3js-formengine-field-item:first');
      $parent.find('.t3js-charcounter').remove();
    }).on('keyup', function() {
      var $field = $(this),
        $parent = $field.parents('.t3js-formengine-field-item:first'),
        maxlengthProperties = FormEngine.getCharacterCounterProperties($field);

      // change class and value
      $parent.find('.t3js-charcounter span').removeClass().addClass(maxlengthProperties.labelClass).text(TYPO3.lang['FormEngine.remainingCharacters'].replace('{0}', maxlengthProperties.remainingCharacters))
    });
    $maxlengthElements.addClass('t3js-charcounter-initialized');
    $(':password').on('focus', function() {
      $(this).attr('type', 'text').select();
    }).on('blur', function() {
      $(this).attr('type', 'password');
    });
  };

  /**
   * Initialize select checkbox element checkboxes
   */
  FormEngine.initializeSelectCheckboxes = function() {
    $('.t3js-toggle-checkboxes').each(function() {
      var $checkbox = $(this);
      var $table = $checkbox.closest('table');
      var $checkboxes = $table.find('.t3js-checkbox');
      var checkIt = $checkboxes.length === $table.find('.t3js-checkbox:checked').length;
      $checkbox.prop('checked', checkIt);
    });
    $(document).on('change', '.t3js-toggle-checkboxes', function(e) {
      e.preventDefault();
      var $checkbox = $(this);
      var $table = $checkbox.closest('table');
      var $checkboxes = $table.find('.t3js-checkbox');
      var checkIt = $checkboxes.length !== $table.find('.t3js-checkbox:checked').length;
      $checkboxes.prop('checked', checkIt);
      $checkbox.prop('checked', checkIt);
    });
    $(document).on('change', '.t3js-checkbox', function(e) {
      FormEngine.updateCheckboxState(this);
    });
  };

  /**
   *
   * @param {HTMLElement} source
   */
  FormEngine.updateCheckboxState = function(source) {
    var $sourceElement = $(source);
    var $table = $sourceElement.closest('table');
    var $checkboxes = $table.find('.t3js-checkbox');
    var checkIt = $checkboxes.length === $table.find('.t3js-checkbox:checked').length;
    $table.find('.t3js-toggle-checkboxes').prop('checked', checkIt);
  };

  /**
   * Get the properties required for proper rendering of the character counter
   *
   * @param {Object} $field
   * @returns {{remainingCharacters: number, labelClass: string}}
   */
  FormEngine.getCharacterCounterProperties = function($field) {
    var fieldText = $field.val(),
      maxlength = $field.attr('maxlength'),
      currentFieldLength = fieldText.length,
      numberOfLineBreaks = (fieldText.match(/\n/g) || []).length, // count line breaks
      remainingCharacters = maxlength - currentFieldLength - numberOfLineBreaks,
      threshold = 15, // hard limit of remaining characters when the label class changes
      labelClass = '';

    if (remainingCharacters < threshold) {
      labelClass = 'label-danger';
    } else if (remainingCharacters < threshold * 2) {
      labelClass = 'label-warning';
    } else {
      labelClass = 'label-info';
    }

    return {
      remainingCharacters: remainingCharacters,
      labelClass: 'label ' + labelClass
    };
  };

  /**
   * Select field filter functions, see TCA option "enableMultiSelectFilterTextfield"
   * and "multiSelectFilterItems"
   */
  FormEngine.SelectBoxFilter = {
    options: {
      fieldContainerSelector: '.t3js-formengine-field-group',
      filterContainerSelector: '.t3js-formengine-multiselect-filter-container',
      filterTextFieldSelector: '.t3js-formengine-multiselect-filter-textfield',
      filterSelectFieldSelector: '.t3js-formengine-multiselect-filter-dropdown',
      itemsToSelectElementSelector: '.t3js-formengine-select-itemstoselect'
    }
  };

  /**
   * Make sure that all selectors and input filters are recognized
   * note: this also works on elements that are loaded asynchronously via AJAX, no need to call this method
   * after an AJAX load.
   */
  FormEngine.SelectBoxFilter.initializeEvents = function() {
    $(document).on('keyup', FormEngine.SelectBoxFilter.options.filterTextFieldSelector, function() {
      var $selectElement = FormEngine.SelectBoxFilter.getSelectElement($(this));
      FormEngine.SelectBoxFilter.filter($selectElement, $(this).val());
    }).on('change', FormEngine.SelectBoxFilter.options.filterSelectFieldSelector, function() {
      var $selectElement = FormEngine.SelectBoxFilter.getSelectElement($(this));
      FormEngine.SelectBoxFilter.filter($selectElement, $(this).val());
    });
  };

  /**
   * Fetch the "itemstoselect" select element where a filter item is attached to
   *
   * @param {Object} $relativeElement
   * @returns {*}
   */
  FormEngine.SelectBoxFilter.getSelectElement = function($relativeElement) {
    var $containerElement = $relativeElement.closest(FormEngine.SelectBoxFilter.options.fieldContainerSelector);
    return $containerElement.find(FormEngine.SelectBoxFilter.options.itemsToSelectElementSelector);
  };

  /**
   * Filter the actual items
   *
   * @param {Object} $selectElement
   * @param {String} filterText
   */
  FormEngine.SelectBoxFilter.filter = function($selectElement, filterText) {
    var $allOptionElements;
    if (!$selectElement.data('alloptions')) {
      $allOptionElements = $selectElement.find('option').clone();
      $selectElement.data('alloptions', $allOptionElements);
    } else {
      $allOptionElements = $selectElement.data('alloptions');
    }

    if (filterText.length > 0) {
      var matchFilter = new RegExp(filterText, 'i');
      $selectElement.html('');
      $allOptionElements.each(function() {
        var $item = $(this);
        if ($item.text().match(matchFilter)) {
          $selectElement.append($item.clone());
        }
      });
    } else {
      $selectElement.html($allOptionElements);
    }
  };

  /**
   * convert all textareas so they grow when it is typed in.
   */
  FormEngine.convertTextareasResizable = function() {
    var $elements = $('.t3js-formengine-textarea');
    if (TYPO3.settings.Textarea && TYPO3.settings.Textarea.autosize && $elements.length) {
      require(['autosize'], function(autosize) {
        autosize($elements);
      });
    }
  };

  /**
   * convert all textareas to enable tab
   */
  FormEngine.convertTextareasEnableTab = function() {
    var $elements = $('.t3js-enable-tab');
    if ($elements.length) {
      require(['taboverride'], function(taboverride) {
        taboverride.set($elements);
      });
    }
  };

  /**
   * Initialize input / text field "null" checkbox CSS overlay if no placeholder is set.
   */
  FormEngine.initializeNullNoPlaceholderCheckboxes = function() {
    $('.t3-form-field-eval-null-checkbox').each(function() {
      // Add disabled class to "t3js-formengine-field-item" if the null checkbox is NOT set,
      // This activates a CSS overlay "disabling" the input field and everything around.
      var $checkbox = $(this).find('input[type="checkbox"]');
      var $fieldItem = $(this).closest('.t3js-formengine-field-item');
      if (!$checkbox.attr('checked')) {
        $fieldItem.addClass('disabled');
      }
    });
  };

  /**
   * Initialize input / text field "null" checkbox placeholder / real field if placeholder is set.
   */
  FormEngine.initializeNullWithPlaceholderCheckboxes = function() {
    $('.t3js-form-field-eval-null-placeholder-checkbox').each(function() {
      FormEngine.toggleCheckboxField($(this).find('input[type="checkbox"]'));
    });
  };

  /**
   * Set initial state of both div's (one containing actual field, other containing placeholder field)
   * depending on whether checkbox is checked or not
   * @param $checkbox
   */
  FormEngine.toggleCheckboxField = function($checkbox) {
    var $item = $checkbox.closest('.t3js-formengine-field-item');
    if ($checkbox.prop('checked')) {
      $item.find('.t3js-formengine-placeholder-placeholder').hide();
      $item.find('.t3js-formengine-placeholder-formfield').show();
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
  FormEngine.reinitialize = function() {
    // Apply "close" button to all input / datetime fields
    if ($('.t3js-clearable').length) {
      require(['TYPO3/CMS/Backend/jquery.clearable'], function() {
        $('.t3js-clearable').clearable();
      });
    }
    if ($('.t3-form-suggest').length) {
      require(['TYPO3/CMS/Backend/FormEngineSuggest'], function(Suggest) {
        Suggest($('.t3-form-suggest'));
      });
    }
    // Apply DatePicker to all date time fields
    if ($('.t3js-datetimepicker').length) {
      require(['TYPO3/CMS/Backend/DateTimePicker'], function(DateTimePicker) {
        DateTimePicker.initialize();
      });
    }

    FormEngine.convertTextareasResizable();
    FormEngine.convertTextareasEnableTab();
    FormEngine.initializeNullNoPlaceholderCheckboxes();
    FormEngine.initializeNullWithPlaceholderCheckboxes();
    FormEngine.initializeInputLinkToggle();
    FormEngine.initializeLocalizationStateSelector();
    FormEngine.initializeRemainingCharacterViews();
  };

  /**
   * Disable the input field on load if localization state selector is set to "parent" or "source"
   */
  FormEngine.initializeLocalizationStateSelector = function() {
    $('.t3js-l10n-state-container').each(function() {
      var $input = $(this).closest('.t3js-formengine-field-item').find('[data-formengine-input-name]');
      var currentState = $(this).find('input[type="radio"]:checked').val();
      if (currentState === 'parent' || currentState === 'source') {
        $input.attr('disabled', 'disabled');
      }
    });
  };

  /**
   * Toggle for input link explanation
   */
  FormEngine.initializeInputLinkToggle = function() {
    var toggleClass = '.t3js-form-field-inputlink-explanation-toggle',
      inputFieldClass = '.t3js-form-field-inputlink-input',
      explanationClass = '.t3js-form-field-inputlink-explanation';

    // if empty, show input field
    $(explanationClass).filter(function() {
      return !$.trim($(this).val());
    }).each(function() {
      var $group = $(this).closest('.t3js-form-field-inputlink'),
        $inputField = $group.find(inputFieldClass),
        $explanationField = $group.find(explanationClass);
      $explanationField.toggleClass('hidden', true);
      $inputField.toggleClass('hidden', false);
      $group.find('.form-control-clearable button.close').toggleClass('hidden', false)
    });

    $(document).on('click', toggleClass, function(e) {
      e.preventDefault();

      var $group = $(this).closest('.t3js-form-field-inputlink'),
        $inputField = $group.find(inputFieldClass),
        $explanationField = $group.find(explanationClass),
        explanationShown;

      explanationShown = !$explanationField.hasClass('hidden');
      $explanationField.toggleClass('hidden', explanationShown);
      $inputField.toggleClass('hidden', !explanationShown);
      $group.find('.form-control-clearable button.close').toggleClass('hidden', !explanationShown)
    });

    $(inputFieldClass).on('change', function() {
      var $group = $(this).closest('.t3js-form-field-inputlink'),
        $inputField = $group.find(inputFieldClass),
        $explanationField = $group.find(explanationClass),
        explanationShown;

      if (!$explanationField.hasClass('hidden')) {

        explanationShown = !$explanationField.hasClass('hidden');
        $explanationField.toggleClass('hidden', explanationShown);
        $inputField.toggleClass('hidden', !explanationShown);
        $group.find('.form-control-clearable button.close').toggleClass('hidden', !explanationShown)
      }
    });
  };

  /**
   * Show modal to confirm closing the document without saving
   */
  FormEngine.preventExitIfNotSaved = function() {
    if ($('form[name="' + FormEngine.formName + '"] .has-change').length > 0) {
      var title = TYPO3.lang['label.confirm.close_without_save.title'] || 'Do you want to quit without saving?';
      var content = TYPO3.lang['label.confirm.close_without_save.content'] || 'You have currently unsaved changes. Are you sure that you want to discard all changes?';
      var $modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: TYPO3.lang['buttons.confirm.close_without_save.no'] || 'No, I will continue editing',
          active: true,
          btnClass: 'btn-default',
          name: 'no'
        },
        {
          text: TYPO3.lang['buttons.confirm.close_without_save.yes'] || 'Yes, discard my changes',
          btnClass: 'btn-warning',
          name: 'yes'
        }
      ]);
      $modal.on('button.clicked', function(e) {
        if (e.target.name === 'no') {
          Modal.dismiss();
        } else if (e.target.name === 'yes') {
          Modal.dismiss();
          FormEngine.closeDocument();
        }
      });
    } else {
      FormEngine.closeDocument();
    }
  };

  /**
   * Show modal to confirm closing the document without saving
   */
  FormEngine.preventSaveIfHasErrors = function() {
    if ($('.has-error').length > 0) {
      var title = TYPO3.lang['label.alert.save_with_error.title'] || 'You have errors in your form!';
      var content = TYPO3.lang['label.alert.save_with_error.content'] || 'Please check the form, there is at least one error in your form.';
      var $modal = Modal.confirm(title, content, Severity.error, [
        {
          text: TYPO3.lang['buttons.alert.save_with_error.ok'] || 'OK',
          btnClass: 'btn-danger',
          name: 'ok'
        }
      ]);
      $modal.on('button.clicked', function(e) {
        if (e.target.name === 'ok') {
          Modal.dismiss();
        }
      });
      return false;
    }
    return true;
  };

  /**
   * Close current open document
   */
  FormEngine.closeDocument = function() {
    document.editform.closeDoc.value = 1;
    document.editform.submit();
  };

  /**
   * Main init function called from outside
   *
   * Sets some options and registers the DOMready handler to initialize further things
   *
   * @param {String} browserUrl
   * @param {Number} mode
   */
  FormEngine.initialize = function(browserUrl, mode) {
    FormEngine.browserUrl = browserUrl;
    FormEngine.Validation.setUsMode(mode);

    $(function() {
      FormEngine.initializeEvents();
      FormEngine.SelectBoxFilter.initializeEvents();
      FormEngine.initializeSelectCheckboxes();
      FormEngine.Validation.initialize();
      FormEngine.reinitialize();
      $('#t3js-ui-block').remove();
    });
  };

  // load required modules to hook in the post initialize function
  if (undefined !== TYPO3.settings.RequireJS && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine']) {
    $.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine'], function(pos, moduleName) {
      require([moduleName]);
    });
  }

  // make the form engine object publicly visible for other objects in the TYPO3 namespace
  TYPO3.FormEngine = FormEngine;

  // return the object in the global space
  return FormEngine;
});
