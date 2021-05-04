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
  customEvalFunctions: {},

  fieldChanged: function(table, uid, field, el) {
    var theField = 'data[' + table + '][' + uid + '][' + field + ']';
    console.warn('The method `TBE_EDITOR.fieldChanged()` is deprecated, consider dispatching the `change` event instead: document.querySelector(\'[name="' + theField + '"]\').dispatchEvent(new Event(\'change\', {bubbles: true, cancelable: true}));');

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
      if ($formField.length > 0) {
        TYPO3.FormEngine.Validation.validateField($formField.get(0));
        if ($humanReadableField.length > 0 && !$formField.is($humanReadableField)) {
          TYPO3.FormEngine.Validation.validateField($humanReadableField.get(0));
        }
      }
    }
  }
};

// backwards compatibility for extensions
var TBE_EDITOR_fieldChanged = TBE_EDITOR.fieldChanged;
