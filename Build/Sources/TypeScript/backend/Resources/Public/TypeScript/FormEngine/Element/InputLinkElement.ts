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
  toggleSelector = '.t3js-form-field-inputlink-explanation-toggle',
  inputFieldSelector = '.t3js-form-field-inputlink-input',
  explanationSelector = '.t3js-form-field-inputlink-explanation',
  iconSelector = '.t3js-form-field-inputlink-icon',
}

class InputLinkElement {
  private element: HTMLSelectElement = null;
  private container: HTMLElement = null;
  private toggleSelector: HTMLButtonElement = null;
  private explanationField: HTMLInputElement = null;
  private icon: HTMLSpanElement = null;

  constructor(elementId: string) {
    $((): void => {
      this.element = <HTMLSelectElement>document.getElementById(elementId);
      this.container = <HTMLElement>this.element.closest('.t3js-form-field-inputlink');
      this.toggleSelector = <HTMLButtonElement>this.container.querySelector(Selectors.toggleSelector);
      this.explanationField = <HTMLInputElement>this.container.querySelector(Selectors.explanationSelector);
      this.icon = <HTMLSpanElement>this.container.querySelector(Selectors.iconSelector);
      this.toggleVisibility(this.explanationField.value === '');
      this.registerEventHandler();
    });
  }

  /**
   * @param {boolean} explanationShown
   */
  private toggleVisibility(explanationShown: boolean): void {
    this.explanationField.classList.toggle('hidden', explanationShown);
    this.element.classList.toggle('hidden', !explanationShown);
    const clearable = this.container.querySelector('.form-control-clearable button.close');
    if (clearable !== null) {
      clearable.classList.toggle('hidden', !explanationShown);
    }
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

export = InputLinkElement;
