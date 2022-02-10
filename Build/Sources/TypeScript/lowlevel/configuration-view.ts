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
 * Module: @typo3/lowlevel/configuration-view
 * JavaScript for Configuration View
 */
class ConfigurationView {
  private searchForm: HTMLFormElement = document.querySelector('#ConfigurationView');
  private searchField: HTMLInputElement = this.searchForm.querySelector('input[name="searchString"]');
  private searchResultShown: boolean = ('' !== this.searchField.value);

  constructor() {
    DocumentService.ready().then((): void => {
      // Respond to browser related clearable event
      new RegularEvent('search', (): void => {
        if (this.searchField.value === '' && this.searchResultShown) {
          this.searchForm.submit();
        }
      }).bindTo(this.searchField);
    });

    if (self.location.hash) {
      // scroll page down, so the just opened subtree is visible after reload and not hidden by doc header
      // Determine scrollTo position, either first ".active" (search) or latest clicked element
      let scrollElement = document.querySelector(self.location.hash);
      if (document.querySelector('.list-tree .active ')) {
        scrollElement = document.querySelector('.list-tree .active ');
      } else if (scrollElement) {
        scrollElement.parentElement.parentElement.classList.add('active');
      }

      if (scrollElement) {
        scrollElement.scrollIntoView({ block: 'center' });
      }
    }
  }
}

export default new ConfigurationView();
