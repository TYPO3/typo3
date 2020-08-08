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
  fieldContainerSelector = '.t3js-formengine-field-group',
  filterTextFieldSelector = '.t3js-formengine-multiselect-filter-textfield',
  filterSelectFieldSelector = '.t3js-formengine-multiselect-filter-dropdown',
}

/**
 * Select field filter functions, see TCA option "multiSelectFilterItems"
 */
class SelectBoxFilter {
  private selectElement: HTMLSelectElement = null;
  private filterText: string = '';
  private $availableOptions: JQuery = null;

  constructor(selectElement: HTMLSelectElement) {
    this.selectElement = selectElement;

    this.initializeEvents();
  }

  private initializeEvents(): void {
    const wizardsElement = this.selectElement.closest('.form-wizards-element');
    if (wizardsElement === null) {
      return;
    }

    wizardsElement.addEventListener('keyup', (e: Event): void => {
      if ((<HTMLElement>e.target).matches(Selectors.filterTextFieldSelector)) {
        this.filter((<HTMLInputElement>e.target).value);
      }
    });
    wizardsElement.addEventListener('change', (e: Event): void => {
      if ((<HTMLElement>e.target).matches(Selectors.filterSelectFieldSelector)) {
        this.filter((<HTMLInputElement>e.target).value);
      }
    });
  }

  /**
   * Filter the actual items
   *
   * @param {string} filterText
   */
  private filter(filterText: string): void {
    this.filterText = filterText;
    if (!this.$availableOptions) {
      this.$availableOptions = $(this.selectElement).find('option').clone();
    }

    this.selectElement.innerHTML = '';
    const matchFilter = new RegExp(filterText, 'i');

    this.$availableOptions.each((i: number, el: HTMLElement): void => {
      if (filterText.length === 0 || el.textContent.match(matchFilter)) {
        this.selectElement.appendChild(el);
      }
    });
  }
}

export = SelectBoxFilter;
