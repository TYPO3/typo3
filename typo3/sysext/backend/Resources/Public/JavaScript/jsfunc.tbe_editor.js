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
  customEvalFunctions: {},

  formname: 'editform',
  isChanged: 0,

  labels: {},

  clearBeforeSettingFormValueFromBrowseWin: [],

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
    if ($humanReadableField.length > 0 && !$formField.is($humanReadableField)) {
      $humanReadableField.get(0).dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
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
var TBE_EDITOR_submitForm = TBE_EDITOR.submitForm;
var TBE_EDITOR_split = TBE_EDITOR.split;
var TBE_EDITOR_curSelected = TBE_EDITOR.curSelected;
var TBE_EDITOR_rawurlencode = TBE_EDITOR.rawurlencode;
var TBE_EDITOR_str_replace = TBE_EDITOR.str_replace;
