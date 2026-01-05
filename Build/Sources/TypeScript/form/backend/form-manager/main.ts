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
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/form/backend/form-manager/main
 * JavaScript for form manager
 * @exports @typo3/form/backend/form-manager/main
 */
class FormManager {
  constructor() {
    DocumentService.ready().then((): void => {
      const searchField = document.getElementById('search_field') as HTMLInputElement;
      if (searchField !== null) {
        const searchResultShown = searchField.value !== '';
        new RegularEvent('search', (): void => {
          if (searchField.value === '' && searchResultShown) {
            searchField.closest('form').requestSubmit();
          }
        }).bindTo(searchField);
      }
    });
  }
}

export default new FormManager();
