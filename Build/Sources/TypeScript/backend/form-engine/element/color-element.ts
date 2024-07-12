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

import RegularEvent from '@typo3/core/event/regular-event';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import { selector } from '@typo3/core/literals';

/**
 * Module: @typo3/backend/form-engine/element/color-element
 *
 * Functionality for the color element
 *
 * @example
 * <typo3-formengine-element-color recordFieldId="some-id">
 *   ...
 * </typo3-formengine-element-color>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class ColorElement extends HTMLElement {
  private element: HTMLInputElement = null;

  public connectedCallback(): void {
    const recordFieldId = this.getAttribute('recordFieldId');
    if (recordFieldId === null) {
      return;
    }

    this.element = this.querySelector<HTMLInputElement>(selector`#${recordFieldId}`);
    if (!this.element) {
      return;
    }

    this.registerEventHandler();

    const swatches = this.hasAttribute('colorPalette') ? this.getAttribute('colorPalette').split(';') : [];
    const opacity = this.hasAttribute('opacity');
    import('@typo3/backend/color-picker').then(({ default: ColorPicker }): void => {
      ColorPicker.initialize(this.element, {
        swatches: swatches,
        opacity: opacity
      });
    });
  }

  private registerEventHandler(): void {
    const hiddenElement: HTMLInputElement|null = document.querySelector(selector`input[name="${this.element.dataset.formengineInputName}"]`);

    new RegularEvent('blur', (e: Event): void => {
      hiddenElement.value = (e.target as HTMLInputElement).value;
    }).bindTo(this.element);

    new RegularEvent('formengine.cp.change', (e: CustomEvent): void => {
      FormEngineValidation.validateField(e.target as HTMLInputElement);
      FormEngineValidation.markFieldAsChanged(e.target as HTMLInputElement);

      document.querySelectorAll('.module-docheader-bar .btn').forEach((btn: HTMLButtonElement): void => {
        btn.classList.remove('disabled');
        btn.disabled = false;
      });
    }).bindTo(this.element);
  }
}

window.customElements.define('typo3-formengine-element-color', ColorElement);
