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
 * Module @typo3/backend/form-engine/field-wizard/value-picker
 *
 * @example
 * <typo3-formengine-valuepicker linked-field="css-selector">
 *   <select>
 * </typo3-formengine-valuepicker>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
export class ValuePicker extends HTMLElement {
  private valuePicker: HTMLSelectElement|null = null;
  private linkedField: HTMLInputElement|HTMLTextAreaElement|null = null;
  private initialValueSet: boolean = false;

  public constructor() {
    super();
    const slot = document.createElement('slot');
    slot.addEventListener('slotchange', () => this.initializeValuePicker(slot));
    this.attachShadow({ mode: 'open' }).append(slot);
  }

  public connectedCallback(): void {
    this.linkedField = document.querySelector(this.getAttribute('linked-field')) as HTMLInputElement|HTMLTextAreaElement|null;
    this.linkedField?.addEventListener('change', this.linkedFieldOnChange);
    this.initializeValuePicker(this.shadowRoot.querySelector('slot'));
  }

  public disconnectedCallback(): void {
    this.linkedField?.removeEventListener('change', this.linkedFieldOnChange);
    this.linkedField = null;
  }

  private initializeValuePicker(slot: HTMLSlotElement): void {
    const picker = (slot.assignedElements()[0] ?? null) as HTMLSelectElement|null;
    if (picker !== null && picker.tagName.toLowerCase() !== 'select') {
      throw new Error(`ValuePicker could not be initialized. Expected <select> child name, but found: ${picker}`);
    }
    if (picker !== this.valuePicker) {
      this.valuePicker?.removeEventListener('change', this.onChange);
      this.valuePicker = picker;
      this.valuePicker?.addEventListener('change', this.onChange);
      this.initialValueSet = false;
    }
    this.setInitialPickerValue();
  }

  private setInitialPickerValue() {
    if (this.linkedField === null || this.valuePicker === null || this.initialValueSet) {
      return;
    }
    const formEngineInputField = (document.getElementsByName(this.linkedField.dataset.formengineInputName)[0] ?? null) as HTMLInputElement|HTMLTextAreaElement;
    if (formEngineInputField !== null) {
      this.selectValue(formEngineInputField.value);
      this.initialValueSet = true;
    }
  }

  private readonly onChange = () => {
    this.setValue();
    this.valuePicker.blur();
  };

  private readonly linkedFieldOnChange = () => {
    if (this.valuePicker === null) {
      return;
    }
    this.selectValue(this.linkedField.value);
  };

  private selectValue (value: string): void {
    this.valuePicker.selectedIndex = Array.from(this.valuePicker.options).findIndex((option): boolean => option.value === value);
  }

  private setValue (): void {
    const selectedValue = this.valuePicker.options[this.valuePicker.selectedIndex].value;
    this.linkedField.value = selectedValue;
    this.linkedField.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
  }
}

window.customElements.define('typo3-formengine-valuepicker', ValuePicker);
