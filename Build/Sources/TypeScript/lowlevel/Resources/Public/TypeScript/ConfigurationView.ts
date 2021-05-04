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

/**
 * Module: TYPO3/CMS/Lowlevel/ConfigurationView
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
      $('html, body').scrollTop((document.documentElement.scrollTop || document.body.scrollTop) - 80);
    }
  }
}

export = new ConfigurationView();
