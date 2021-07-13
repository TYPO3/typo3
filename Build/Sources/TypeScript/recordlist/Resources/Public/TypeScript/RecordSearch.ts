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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

enum Selectors {
  searchFieldSelector = '#search_field',
}

/**
 * Module: TYPO3/CMS/Recordlist/RecordSearch
 * Usability improvements for the record search
 * @exports TYPO3/CMS/Recordlist/RecordSearch
 */
class RecordSearch {
  private searchField: HTMLInputElement = document.querySelector(Selectors.searchFieldSelector);
  private activeSearch: boolean = this.searchField ? (this.searchField.value !== '') : false;

  constructor() {
    DocumentService.ready().then((): void => {
      // Respond to browser related clearable event
      if (this.searchField) {
        new RegularEvent('search', (): void => {
          if (this.searchField.value === '' && this.activeSearch) {
            this.searchField.closest('form').submit();
          }
        }).bindTo(this.searchField);
      }
    });
  }
}

export = new RecordSearch();
