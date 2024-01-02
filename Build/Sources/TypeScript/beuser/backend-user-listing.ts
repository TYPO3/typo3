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
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/beuser/backend-user-listing
 * JavaScript for backend user listing
 * @exports @typo3/beuser/backend-user-listing
 */
class BackendUserListing {
  private readonly searchField: HTMLInputElement = document.querySelector('#tx_Beuser_username');
  private readonly activeSearch: boolean = this.searchField ? (this.searchField.value !== '') : false;

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

export default new BackendUserListing();
