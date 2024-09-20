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

import SortableTable from '@typo3/backend/sortable-table';

/**
 * Module: @typo3/indexed-search/statistics
 *
 * Functionality for the indexed search statistics module
 *
 * @example
 * <typo3-indexed-search-statistics>
 *   ...
 * </typo3-indexed-search-statistics>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class IndexedSearchStatistics extends HTMLElement {
  constructor() {
    super();
    const slot: HTMLSlotElement = document.createElement('slot');
    slot.addEventListener('slotchange', () => this.initializeWordList(slot));
    this.attachShadow({ mode: 'open' }).append(slot);
  }

  public connectedCallback(): void {
    this.initializeWordList(this.shadowRoot.querySelector('slot'));
  }

  private initializeWordList(slot: HTMLSlotElement): void {
    const wordList: HTMLTableElement = (slot.assignedElements()[0].children[0] ?? null) as HTMLTableElement|null;
    if (wordList === null || wordList.tagName.toLowerCase() !== 'table') {
      throw new Error(`Sortable table could not be initialized. Expected <table> child name, but found: ${wordList}`);
    }
    new SortableTable(wordList);
  }
}

window.customElements.define('typo3-indexed-search-statistics', IndexedSearchStatistics);
