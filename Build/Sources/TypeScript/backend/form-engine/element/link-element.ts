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

import { selector } from '@typo3/core/literals';

enum Selectors {
  toggleSelector = '.t3js-form-field-link-explanation-toggle',
  inputFieldSelector = '.t3js-form-field-link-input',
  explanationSelector = '.t3js-form-field-link-explanation',
  iconSelector = '.t3js-form-field-link-icon',
  containerSelector = '.t3js-form-field-link',
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
  constructor() {
    super();

    this.addEventListener('click', (e: Event) => this.handleClick(e));
    this.addEventListener('change', (e: Event) => this.handleChange(e));
  }

  private get element(): HTMLSelectElement {
    const recordFieldId = this.getAttribute('recordFieldId');
    if (recordFieldId === null) {
      throw new Error('Missing recordFieldId attribute on <typo3-formengine-element-link>');
    }

    const element = this.querySelector<HTMLSelectElement>(selector`#${recordFieldId}`);
    if (element === null) {
      throw new Error(`recordFieldId #${recordFieldId} not found in <typo3-formengine-element-link>`);
    }

    return element;
  }

  private get container(): HTMLElement {
    return this.element.closest<HTMLElement>(Selectors.containerSelector);
  }

  private get toggleSelector(): HTMLButtonElement {
    return this.container.querySelector<HTMLButtonElement>(Selectors.toggleSelector);
  }

  private get explanationField(): HTMLInputElement {
    return this.container.querySelector<HTMLInputElement>(Selectors.explanationSelector);
  }

  private get icon(): HTMLSpanElement {
    return this.container.querySelector<HTMLSpanElement>(Selectors.iconSelector);
  }

  private handleClick(e: Event): void {
    const initiator = e.target as Element;
    const isToggleButton = initiator.closest(Selectors.toggleSelector) !== null;
    if (isToggleButton) {
      e.preventDefault();
      const explanationHidden = this.explanationField.hasAttribute('hidden');
      if (explanationHidden) {
        this.showExplanation();
      } else {
        this.hideExplanation();
      }
    }
  }

  private handleChange(e: Event): void {
    const initiator = e.target as Element;
    const isInputField = initiator.closest(Selectors.inputFieldSelector) !== null;
    if (isInputField) {
      const explanationVisible = !this.explanationField.hasAttribute('hidden');
      if (explanationVisible) {
        this.hideExplanation();
      }
      this.disableToggle();
      this.clearIcon();
    }
  }

  private showExplanation(): void {
    this.explanationField.removeAttribute('hidden');
    this.element.setAttribute('hidden', '');
  }

  private hideExplanation(): void {
    this.explanationField.setAttribute('hidden', '');
    this.element.removeAttribute('hidden');
  }

  private disableToggle(): void {
    this.toggleSelector.setAttribute('disabled', '');
  }

  private clearIcon(): void {
    this.icon.replaceChildren();
  }
}

window.customElements.define('typo3-formengine-element-link', LinkElement);
