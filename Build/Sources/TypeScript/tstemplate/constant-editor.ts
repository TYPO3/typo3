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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

enum Selectors {
  editIconSelector = '.t3js-toggle',
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
    DocumentService.ready().then((document: Document): void => {
      const colorInputElements: NodeListOf<HTMLInputElement> = document.querySelectorAll(Selectors.colorInputSelector);
      if (colorInputElements.length) {
        import('@typo3/backend/color-picker').then(({default: ColorPicker}): void => {
          colorInputElements.forEach((element: HTMLInputElement): void => {
            ColorPicker.initialize(element);
            new RegularEvent('blur', this.updateFormFragment).bindTo(element);
          });
        });
      }

      this.registerEvents();
    });
  }

  private registerEvents(): void {
    new RegularEvent('click', this.changeProperty)
      .delegateTo(document, Selectors.editIconSelector);
    new RegularEvent('change', this.updateFormFragment)
      .delegateTo(document, Selectors.formFieldsSelector);
  }

  /**
   * Sets the # suffix for the form action to jump directly to the last updated Constant Editor field on submit.
   * Removes any existing "#" suffixes in case multiple fields were updated
   */
  private updateFormFragment(this: HTMLInputElement): void {
    const fragment = this.dataset.formUpdateFragment;
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
  private changeProperty(this: HTMLElement): void {
    const constantName: string = this.getAttribute('rel');
    const defaultDiv: HTMLDivElement = document.getElementById('defaultTS-' + constantName) as HTMLDivElement;
    const userDiv: HTMLDivElement = document.getElementById('userTS-' + constantName) as HTMLDivElement;
    const checkBox: HTMLInputElement = document.getElementById('check-' + constantName) as HTMLInputElement;
    const toggleState: string = this.dataset.bsToggle;

    if (toggleState === 'edit') {
      defaultDiv.style.display = 'none';
      userDiv.style.removeProperty('display');
      userDiv.querySelectorAll('input').forEach((element: HTMLInputElement): void => {element.style.background = '#fdf8bd'});
      checkBox.removeAttribute('disabled');
      checkBox.setAttribute('checked', 'checked');
    } else if (toggleState === 'undo') {
      userDiv.style.display = 'none';
      defaultDiv.style.removeProperty('display');
      checkBox.value = '';
      checkBox.setAttribute('disabled', 'disabled');
    }
  }
}

export default new ConstantEditor();
