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

enum InsertModes {
  append = 'append',
  replace = 'replace',
  prepend = 'prepend'
}

/**
 * Module @typo3/backend/form-engine/field-wizard/value-picker
 *
 * @example
 * <typo3-formengine-valuepicker mode="prepend" linked-field="css-selector">
 *   <select>
 * </typo3-formengine-valuepicker>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
export class ValuePicker extends HTMLElement {
  private valuePicker: HTMLSelectElement;
  private linkedField: HTMLInputElement|HTMLTextAreaElement;

  public connectedCallback(): void {
    this.valuePicker = this.querySelector('select') as HTMLSelectElement;
    if (this.valuePicker !== null) {
      this.valuePicker.addEventListener('change', this.onChange);
    }
    this.linkedField = document.querySelector(this.getAttribute('linked-field')) as HTMLInputElement|HTMLTextAreaElement;
    if (this.linkedField !== null) {
      this.linkedField.addEventListener('change', this.linkedFieldOnChange);

      // Set initial value
      if (this.getInsertMode() === InsertModes.replace) {
        const formEngineInputField: HTMLInputElement|HTMLTextAreaElement = document.getElementsByName(this.linkedField.dataset.formengineInputName)[0] as HTMLInputElement|HTMLTextAreaElement;
        formEngineInputField && this.selectValue(formEngineInputField.value);
      }
    }
  }

  public disconnectedCallback(): void {
    if (this.valuePicker !== null) {
      this.valuePicker.removeEventListener('change', this.onChange);
      this.valuePicker = null;
    }
  }

  private readonly onChange = () => {
    this.setValue();
    this.valuePicker.blur();
  }

  private readonly linkedFieldOnChange = () => {
    if (this.getInsertMode() === InsertModes.replace) {
      this.selectValue(this.linkedField.value);
    } else {
      this.valuePicker.selectedIndex = 0;
    }
  }

  private selectValue (value: string): void {
    this.valuePicker.selectedIndex = Array.from(this.valuePicker.options).findIndex((option): boolean => option.value === value);
  }

  private getInsertMode (): InsertModes {
    return this.getAttribute('mode') as InsertModes ?? InsertModes.replace;
  }

  private setValue (): void {
    const selectedValue = this.valuePicker.options[this.valuePicker.selectedIndex].value;

    switch (this.getInsertMode()) {
      case InsertModes.append:
        this.linkedField.value += selectedValue;
        break;
      case InsertModes.prepend:
        this.linkedField.value = selectedValue + this.linkedField.value;
        break;
      default:
        this.linkedField.value = selectedValue;
        break;
    }
    this.linkedField.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
  }
}

window.customElements.define('typo3-formengine-valuepicker', ValuePicker);
