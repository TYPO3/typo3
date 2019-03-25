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

import * as $ from 'jquery';
import 'TYPO3/CMS/Backend/jquery.clearable';

/**
 * Module: TYPO3/CMS/Beuser/BackendUserListing
 * JavaScript for backend user listing
 * @exports TYPO3/CMS/Beuser/BackendUserListing
 */
class BackendUserListing {
  constructor() {
    let $searchFields = $('#tx_Beuser_username');
    let searchResultShown = ('' !== $searchFields.first().val());

    // make search field clearable
    $searchFields.clearable({
      onClear: (e: JQueryEventObject): void => {
        if (searchResultShown) {
          $(e.currentTarget).closest('form').submit();
        }
      },
    });
  }
}

export = new BackendUserListing();
