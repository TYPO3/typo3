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

import $ from 'jquery';

enum Selectors {
  editIconSelector = '.t3js-toggle',
  colorSelectSelector = '.t3js-color-select',
  colorInputSelector = '.t3js-color-input',
  formFieldsSelector = '.tstemplate-constanteditor [data-form-update-fragment]'
}

/**
 * Module: @typo3/tstemplate/constant-editor
 * Various functions related to the Constant Editor
 * e.g. updating the field and working with colors
 */
class ConstantEditor {
  constructor() {
    // no DOMready needed since only events for document are registered
    $(document)
      .on('click', Selectors.editIconSelector, this.changeProperty)
      .on('change', Selectors.colorSelectSelector, this.updateColorFromSelect)
      .on('blur', Selectors.colorInputSelector, this.updateColorFromInput)
      .on('change', Selectors.formFieldsSelector, this.updateFormFragment)
    ;
  }

  /**
   * Sets the # suffix for the form action to jump directly to the last updated Constant Editor field on submit.
   * Removes any existing "#" suffixes in case multiple fields were updated
   */
  private updateFormFragment = (evt: JQueryEventObject): void => {
    const $formField = $(evt.currentTarget);
    const fragment = $formField.attr('data-form-update-fragment');
    let formTargetAction = document.forms[0].action;
    // Strip away any existing fragments
    if (formTargetAction.indexOf('#') !== -1) {
      formTargetAction = formTargetAction.substring(0, formTargetAction.indexOf('#'));
    }
    document.forms[0].action = formTargetAction + '#' + fragment;
  }

  /**
   * initially register event listeners
   */
  private changeProperty = (evt: JQueryEventObject): void => {
    const $editIcon = $(evt.currentTarget);
    const constantName = $editIcon.attr('rel');
    const $defaultDiv = $('#defaultTS-' + constantName);
    const $userDiv = $('#userTS-' + constantName);
    const $checkBox = $('#check-' + constantName);
    const toggleState = $editIcon.data('bsToggle');

    if (toggleState === 'edit') {
      $defaultDiv.hide();
      $userDiv.show();
      $userDiv.find('input').css({background: '#fdf8bd'});
      $checkBox.prop('disabled', false).prop('checked', true);
    } else if (toggleState === 'undo') {
      $userDiv.hide();
      $defaultDiv.show();
      $checkBox.val('').prop('disabled', true);
    }
  }

  /**
   * updates the color from a dropdown
   */
  private updateColorFromSelect = (evt: JQueryEventObject): void => {
    const $colorSelect = $(evt.currentTarget);
    let constantName = $colorSelect.attr('rel');
    let colorValue = $colorSelect.val();

    $('#input-' + constantName).val(colorValue);
    $('#colorbox-' + constantName).css({background: colorValue});
  }

  /**
   * updates the color from an input field
   */
  private updateColorFromInput = (evt: JQueryEventObject): void => {
    const $colorInput = $(evt.currentTarget);
    let constantName = $colorInput.attr('rel');
    let colorValue = $colorInput.val();

    $('#colorbox-' + constantName).css({background: colorValue});
    $('#select-' + constantName).children().each((i: number, option: Element) => {
      (<HTMLOptionElement>option).selected = ((<HTMLOptionElement>option).value === colorValue);
    });
  }
}

export default new ConstantEditor();
