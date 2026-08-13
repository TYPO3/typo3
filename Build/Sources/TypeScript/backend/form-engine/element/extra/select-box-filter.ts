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
import TextNormalizer from '@typo3/core/utility/text-normalizer';

enum Selectors {
  fieldContainerSelector = '.t3js-formengine-field-group',
  filterTextFieldSelector = '.t3js-formengine-multiselect-filter-textfield',
  filterSelectFieldSelector = '.t3js-formengine-multiselect-filter-dropdown',
}

/**
 * Select field filter functions, see TCA option "multiSelectFilterItems"
 */
class SelectBoxFilter {
  private readonly selectElement: HTMLSelectElement = null;
  private availableOptions: NodeListOf<HTMLOptionElement> = null;
  private foldedLabels: string[] = null;

  constructor(selectElement: HTMLSelectElement) {
    this.selectElement = selectElement;

    this.initializeEvents();
  }

  private static toggleOptGroup(option: HTMLOptionElement): void {
    const optGroup = <HTMLOptGroupElement>option.parentElement;
    if (!(optGroup instanceof HTMLOptGroupElement)) {
      return;
    }
    if (optGroup.querySelectorAll('option:not([hidden]):not([disabled]):not(.hidden)').length === 0) {
      optGroup.hidden = true;
    } else {
      optGroup.hidden = false;
      optGroup.disabled = false;
      optGroup.classList.remove('hidden');
    }
  }

  private initializeEvents(): void {
    const wizardsElement = this.selectElement.closest('.form-wizards-wrap');
    if (wizardsElement === null) {
      return;
    }

    new RegularEvent('input', (e: Event): void => {
      this.filter((<HTMLInputElement>e.target).value);
    }).delegateTo(wizardsElement, Selectors.filterTextFieldSelector);

    new RegularEvent('search', (e: Event): void => {
      this.filter((<HTMLInputElement>e.target).value);
    }).delegateTo(wizardsElement, Selectors.filterTextFieldSelector);

    new RegularEvent('change', (e: Event): void => {
      this.filter((<HTMLInputElement>e.target).value);
    }).delegateTo(wizardsElement, Selectors.filterSelectFieldSelector);
  }

  /**
   * Filter the actual items
   *
   * @param {string} filterText
   */
  private filter(filterText: string): void {
    if (this.availableOptions === null) {
      this.availableOptions = this.selectElement.querySelectorAll('option');
      this.foldedLabels = Array.from(this.availableOptions, (option: HTMLOptionElement): string =>
        TextNormalizer.foldCaseAndDiacritics(TextNormalizer.normalizeInvisibleCharacters(option.textContent)));
    }

    // A plain substring match, no RegExp involved, therefore nothing to escape either
    const foldedFilterText = TextNormalizer.foldCaseAndDiacritics(TextNormalizer.normalizeInvisibleCharacters(filterText));

    this.availableOptions.forEach((option: HTMLOptionElement, index: number): void => {
      option.hidden = filterText.length > 0 && !this.foldedLabels[index].includes(foldedFilterText);
      SelectBoxFilter.toggleOptGroup(option);
    });
  }
}

export default SelectBoxFilter;
