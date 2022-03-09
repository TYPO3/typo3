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

enum Selectors {
  toggleSelector = '.t3js-form-field-link-explanation-toggle',
  inputFieldSelector = '.t3js-form-field-link-input',
  explanationSelector = '.t3js-form-field-link-explanation',
  iconSelector = '.t3js-form-field-link-icon',
}


/**
 * Module: @typo3/backend/form-engine/element/link-element
 *
 * Functionality for the link element
 *
 * @example
 * <typo3-formengine-element-link recordFieldId="some-id">
 *   ...
 * </typo3-formengine-element-link>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class LinkElement extends HTMLElement {
  private element: HTMLSelectElement = null;
  private container: HTMLElement = null;
  private toggleSelector: HTMLButtonElement = null;
  private explanationField: HTMLInputElement = null;
  private icon: HTMLSpanElement = null;

  public connectedCallback(): void {
    this.element = <HTMLSelectElement>this.querySelector('#' + (this.getAttribute('recordFieldId') || '' as string));

    if (!this.element) {
      return;
    }

    this.container = <HTMLElement>this.element.closest('.t3js-form-field-link');
    this.toggleSelector = <HTMLButtonElement>this.container.querySelector(Selectors.toggleSelector);
    this.explanationField = <HTMLInputElement>this.container.querySelector(Selectors.explanationSelector);
    this.icon = <HTMLSpanElement>this.container.querySelector(Selectors.iconSelector);
    this.toggleVisibility(this.explanationField.value === '');
    this.registerEventHandler();
  }

  /**
   * @param {boolean} explanationShown
   */
  private toggleVisibility(explanationShown: boolean): void {
    this.explanationField.classList.toggle('hidden', explanationShown);
    this.element.classList.toggle('hidden', !explanationShown);
  }

  private registerEventHandler(): void {
    this.toggleSelector.addEventListener('click', (e: Event): void => {
      e.preventDefault();

      const explanationShown = !this.explanationField.classList.contains('hidden');
      this.toggleVisibility(explanationShown);
    });

    this.container.querySelector(Selectors.inputFieldSelector).addEventListener('change', (): void => {
      const explanationShown = !this.explanationField.classList.contains('hidden');
      if (explanationShown) {
        this.toggleVisibility(explanationShown);
      }
      this.disableToggle();
      this.clearIcon();
    });
  }

  private disableToggle(): void {
    this.toggleSelector.classList.add('disabled');
    this.toggleSelector.setAttribute('disabled', 'disabled');
  }

  private clearIcon(): void {
    this.icon.innerHTML = '';
  }
}

window.customElements.define('typo3-formengine-element-link', LinkElement);
