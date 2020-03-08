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
 * Contains JavaScript for TYPO3 Core Form generator - AKA "TCEforms"
 */

var TBE_EDITOR = {
  /* Example:
    elements: {
      'data-parentPid-table-uid': {
        'field': {
          'range':		[0, 100],
          'rangeImg':		'',
          'required':		true,
          'requiredImg':	''
        }
      }
    },
  */

  elements: {},
  nested: {'field': {}, 'level': {}},
  ignoreElements: [],
  actionChecks: {submit: []},
  customEvalFunctions: {},

  formname: 'editform',
  isChanged: 0,

  doSaveFieldName: 0,

  labels: {},

  clearBeforeSettingFormValueFromBrowseWin: [],

  // Handling of data structures:
  removeElement: function(record) {
    if (TBE_EDITOR.elements && TBE_EDITOR.elements[record]) {
      delete(TBE_EDITOR.elements[record]);
    }
  },
  removeElementArray: function(removeStack) {
    if (removeStack && removeStack.length) {
      TBE_EDITOR.ignoreElements = removeStack;
      for (var i = removeStack.length; i >= 0; i--) {
        TBE_EDITOR.removeElement(removeStack[i]);
      }
      TBE_EDITOR.ignoreElements = [];
    }
  },
  getElement: function(record, field, type) {
    var result = null;
    var element;

    if (TBE_EDITOR.elements && TBE_EDITOR.elements[record] && TBE_EDITOR.elements[record][field]) {
      element = TBE_EDITOR.elements[record][field];
      if (type) {
        if (element[type]) result = element;
      } else {
        result = element;
      }
    }

    return result;
  },
  checkElements: function() {
    return (document.getElementsByClassName('has-error').length == 0);
  },
  addActionChecks: function(type, checks) {
    TBE_EDITOR.actionChecks[type].push(checks);
  },

  fieldChanged_fName: function(fName, el) {
    var idx = 2;
    var table = TBE_EDITOR.split(fName, "[", idx);
    var uid = TBE_EDITOR.split(fName, "[", idx + 1);
    var field = TBE_EDITOR.split(fName, "[", idx + 2);

    table = table.substr(0, table.length - 1);
    uid = uid.substr(0, uid.length - 1);
    field = field.substr(0, field.length - 1);
    TBE_EDITOR.fieldChanged(table, uid, field, el);
  },
  fieldChanged: function(table, uid, field, el) {
    var theField = 'data[' + table + '][' + uid + '][' + field + ']';
    TBE_EDITOR.isChanged = 1;

    // modify the "field has changed" info by adding a class to the container element (based on palette or main field)
    var $formField = $('[name="' + el + '"]');
    var $humanReadableField = $('[data-formengine-input-name="' + el + '"]');
    if (!$formField.is($humanReadableField)) {
      $humanReadableField.triggerHandler('change');
    }
    // add class to palette field
    $formField.closest('.t3js-formengine-palette-field').addClass('has-change');

    if (TYPO3.FormEngine && TYPO3.FormEngine.Validation) {
      TYPO3.FormEngine.Validation.updateInputField(theField);
      TYPO3.FormEngine.Validation.validate();
    }
  },
  isFormChanged: function(noAlert) {
    if (TBE_EDITOR.isChanged && !noAlert && confirm(TYPO3.lang['FormEngine.fieldsChanged'])) {
      return 0;
    }
    return TBE_EDITOR.isChanged;
  },
  checkAndDoSubmit: function(sendAlert) {
    if (TBE_EDITOR.checkSubmit(sendAlert)) {
      TBE_EDITOR.submitForm();
    }
  },
  /**
   * Checks if the form can be submitted according to any possible restrains like required values, item numbers etc.
   * Returns true if the form can be submitted, otherwise false (and might issue an alert message, if "sendAlert" is 1)
   * If "sendAlert" is false, no error message will be shown upon false return value (if "1" then it will).
   * If "sendAlert" is "-1" then the function will ALWAYS return true regardless of constraints (except if login has expired) - this is used in the case where a form field change requests a form update and where it is accepted that constraints are not observed (form layout might change so other fields are shown...)
   */
  checkSubmit: function(sendAlert) {
    var funcIndex, funcMax, funcRes;
    var OK = 1;
    var STOP = 0;

    // $this->additionalJS_submit:
    if (TBE_EDITOR.actionChecks && TBE_EDITOR.actionChecks.submit) {
      for (funcIndex = 0, funcMax = TBE_EDITOR.actionChecks.submit.length; funcIndex < funcMax; funcIndex++) {
        try {
          eval(TBE_EDITOR.actionChecks.submit[funcIndex]);
        } catch (error) {
        }
      }
    }

    if (STOP) {
      // return false immediately, if the code in additionalJS_submit set STOP variable.
      return false;
    }

    if (!OK) {
      if (!confirm(unescape("SYSTEM ERROR: One or more Rich Text Editors on the page could not be contacted. This IS an error, although it should not be regular.\nYou can save the form now by pressing OK, but you will loose the Rich Text Editor content if you do.\n\nPlease report the error to your administrator if it persists."))) {
        return false;
      } else {
        OK = 1;
      }
    }
    if (!TBE_EDITOR.checkElements()) {
      OK = 0;
    }

    if (OK || sendAlert == -1) {
      return true;
    } else {
      if (sendAlert) {
        var t = (opener != null && typeof opener.top.TYPO3 !== 'undefined' ? opener.top : top);
        t.TYPO3.Modal.confirm(
          t.TYPO3.lang['alert'] || 'Alert',
          TYPO3.lang['FormEngine.fieldsMissing'],
          t.TYPO3.Severity.error,
          [
            {
              text: t.TYPO3.lang['button.ok'] || 'OK',
              active: true,
              btnClass: 'btn-default',
              name: 'ok'
            }
          ]
        ).on('button.clicked', function(e) {
          t.TYPO3.Modal.dismiss();
        });
      }
      return false;
    }
  },
  submitForm: function() {
    if (TBE_EDITOR.doSaveFieldName) {
      document[TBE_EDITOR.formname][TBE_EDITOR.doSaveFieldName].value = 1;
    }

    const elements = [
      'button[form]',
      'button[name^="_save"]',
      'a[data-name^="_save"]',
      'button[name="CMD"][value^="save"]',
      'a[data-name="CMD"][data-value^="save"]',
    ].join(',');

    const button = document.querySelector(elements);
    if (button !== null) {
      button.disabled = true;

      TYPO3.Icons.getIcon('spinner-circle-dark', TYPO3.Icons.sizes.small).done(function (markup) {
        button.querySelector('.t3js-icon').outerHTML = markup;
      });
    }

    // Set a short timeout to allow other JS processes to complete, in particular those from
    // EXT:backend/Resources/Public/JavaScript/FormEngine.js (reference: http://forge.typo3.org/issues/58755).
    // TODO: This should be solved in a better way when this script is refactored.
    window.setTimeout(function() {
      var formElement = document.getElementsByName(TBE_EDITOR.formname).item(0);
      $('[data-active-password]:not([type=password])').each(
        function(index, element) {
          element.setAttribute('type', 'password');
          element.blur();
        }
      );
      formElement.submit();
    }, 100);
  },
  split: function(theStr1, delim, index) {
    var theStr = "" + theStr1;
    var lengthOfDelim = delim.length;
    sPos = -lengthOfDelim;
    if (index < 1) {
      index = 1;
    }
    for (var a = 1; a < index; a++) {
      sPos = theStr.indexOf(delim, sPos + lengthOfDelim);
      if (sPos == -1) {
        return null;
      }
    }
    ePos = theStr.indexOf(delim, sPos + lengthOfDelim);
    if (ePos == -1) {
      ePos = theStr.length;
    }
    return (theStr.substring(sPos + lengthOfDelim, ePos));
  },
  curSelected: function(theField) {
    var fObjSel = $('[data-formengine-input-name="' + theField + '"]').get(1);
    var retVal = "";
    if (fObjSel) {
      if (fObjSel.type == 'select-multiple' || fObjSel.type == 'select-one') {
        var l = fObjSel.length;
        for (a = 0; a < l; a++) {
          if (fObjSel.options[a].selected == 1) {
            retVal += fObjSel.options[a].value + ",";
          }
        }
      }
    }
    return retVal;
  },
  rawurlencode: function(str, maxlen) {
    var output = str;
    if (maxlen) output = output.substr(0, 200);
    output = encodeURIComponent(output);
    return output;
  },
  str_replace: function(match, replace, string) {
    var input = '' + string;
    var matchStr = '' + match;
    if (!matchStr) {
      return string;
    }
    var output = '';
    var pointer = 0;
    var pos = input.indexOf(matchStr);
    while (pos != -1) {
      output += '' + input.substr(pointer, pos - pointer) + replace;
      pointer = pos + matchStr.length;
      pos = input.indexOf(match, pos + 1);
    }
    output += '' + input.substr(pointer);
    return output;
  }
};

// backwards compatibility for extensions
var TBE_EDITOR_setHiddenContent = TBE_EDITOR.setHiddenContent;
var TBE_EDITOR_isChanged = TBE_EDITOR.isChanged;
var TBE_EDITOR_fieldChanged_fName = TBE_EDITOR.fieldChanged_fName;
var TBE_EDITOR_fieldChanged = TBE_EDITOR.fieldChanged;
var TBE_EDITOR_isFormChanged = TBE_EDITOR.isFormChanged;
var TBE_EDITOR_checkAndDoSubmit = TBE_EDITOR.checkAndDoSubmit;
var TBE_EDITOR_checkSubmit = TBE_EDITOR.checkSubmit;
var TBE_EDITOR_submitForm = TBE_EDITOR.submitForm;
var TBE_EDITOR_split = TBE_EDITOR.split;
var TBE_EDITOR_curSelected = TBE_EDITOR.curSelected;
var TBE_EDITOR_rawurlencode = TBE_EDITOR.rawurlencode;
var TBE_EDITOR_str_replace = TBE_EDITOR.str_replace;


var typo3form = {
  fieldSet: function(theField, evallist, is_in, checkbox, checkboxValue) {
    if (document[TBE_EDITOR.formname][theField]) {
      var theFObj = new evalFunc_dummy(evallist, is_in, checkbox, checkboxValue);
      var theValue = document[TBE_EDITOR.formname][theField].value;
      if (checkbox && theValue == checkboxValue) {
        document.querySelector('form[name="' + TBE_EDITOR.formname + '"] [data-formengine-input-name="' + theField + '"]').value = "";
        if (document[TBE_EDITOR.formname][theField + "_cb"]) document[TBE_EDITOR.formname][theField + "_cb"].checked = "";
      } else {
        document.querySelector('form[name="' + TBE_EDITOR.formname + '"] [data-formengine-input-name="' + theField + '"]').value = evalFunc.outputObjValue(theFObj, theValue);
        if (document[TBE_EDITOR.formname][theField + "_cb"]) document[TBE_EDITOR.formname][theField + "_cb"].checked = "on";
      }
    }
  },
  fieldGet: function(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off, checkSetValue) {
    if (document[TBE_EDITOR.formname][theField]) {
      var theFObj = new evalFunc_dummy(evallist, is_in, checkbox, checkboxValue);
      if (checkbox_off) {
        if (document[TBE_EDITOR.formname][theField + "_cb"].checked) {
          var split = evallist.split(',');
          for (var i = 0; split.length > i; i++) {
            var el = split[i].replace(/ /g, '');
            if (el == 'datetime' || el == 'date') {
              var now = new Date();
              checkSetValue = Date.parse(now) / 1000 - now.getTimezoneOffset() * 60;
              break;
            } else if (el == 'time' || el == 'timesec') {
              checkSetValue = evalFunc_getTimeSecs(new Date());
              break;
            }
          }
          document[TBE_EDITOR.formname][theField].value = checkSetValue;
        } else {
          document[TBE_EDITOR.formname][theField].value = checkboxValue;
        }
      } else {
        document[TBE_EDITOR.formname][theField].value = evalFunc.evalObjValue(theFObj, document.querySelector('form[name="' + TBE_EDITOR.formname + '"] [data-formengine-input-name="' + theField + '"]').value);
      }
      typo3form.fieldSet(theField, evallist, is_in, checkbox, checkboxValue);
    }
  }
};

// @TODO: This function is a copy from jsfunc.evalfield.js
// @TODO: Remove it later, after TBE_EDITOR is not used anymore.
function evalFunc_dummy(evallist, is_in, checkbox, checkboxValue) {
  this.evallist = evallist;
  this.is_in = is_in;
  this.checkboxValue = checkboxValue;
  this.checkbox = checkbox;
}

// backwards compatibility for extensions
var typo3FormFieldSet = typo3form.fieldSet;
var typo3FormFieldGet = typo3form.fieldGet;
