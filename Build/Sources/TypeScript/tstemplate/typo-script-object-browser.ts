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

import '@typo3/backend/input/clearable';

/**
 * Module: @typo3/tstemplate/typo-script-object-browser
 * JavaScript for TypoScript Object Browser
 * @exports @typo3/tstemplate/typo-script-object-browser
 */
class TypoScriptObjectBrowser {
  private searchField: HTMLInputElement;
  private readonly searchResultShown: boolean;

  constructor() {
    this.searchField = document.querySelector('input[name="search_field"]');
    this.searchResultShown = ('' !== this.searchField.value);

    this.searchField.clearable({
      onClear: (input: HTMLInputElement): void => {
        if (this.searchResultShown) {
          input.closest('form').submit();
        }
      },
    });

    if (self.location.hash) {
      window.scrollTo(window.pageXOffset, window.pageYOffset - 40);
    }
  }
}

export default new TypoScriptObjectBrowser();
