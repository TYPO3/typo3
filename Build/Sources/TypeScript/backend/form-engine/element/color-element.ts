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
    this.element = document.getElementById((this.getAttribute('recordFieldId') || '' as string)) as HTMLInputElement;

    if (!this.element) {
      return;
    }

    this.registerEventHandler();
    import('@typo3/backend/color-picker').then(({ default: ColorPicker }): void => {
      ColorPicker.initialize(this.element);
    });
  }

  private registerEventHandler(): void {
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
