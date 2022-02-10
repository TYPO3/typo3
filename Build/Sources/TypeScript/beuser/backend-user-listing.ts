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
 * Module: @typo3/beuser/backend-user-listing
 * JavaScript for backend user listing
 * @exports @typo3/beuser/backend-user-listing
 */
class BackendUserListing {
  constructor() {
    let searchField: HTMLInputElement;
    if ((searchField = document.getElementById('tx_Beuser_username') as HTMLInputElement) !== null) {
      const searchResultShown = ('' !== searchField.value);

      // make search field clearable
      searchField.clearable({
        onClear: (input: HTMLInputElement): void => {
          if (searchResultShown) {
            input.closest('form').submit();
          }
        },
      });
    }
  }
}

export default new BackendUserListing();
