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
import SortableTable from '@typo3/backend/sortable-table';

/**
 * Module: @typo3/indexed-search/main
 * Sorting of table cells
 * @exports @typo3/indexed-search/main
 */
class IndexedSearch {
  constructor() {
    DocumentService.ready().then((): void => {
      const wordList = document.getElementById('typo3-words-list');
      if (wordList !== null) {
        if (wordList instanceof HTMLTableElement) {
          new SortableTable(wordList);
        }
      }
    });
  }
}

export default new IndexedSearch();
