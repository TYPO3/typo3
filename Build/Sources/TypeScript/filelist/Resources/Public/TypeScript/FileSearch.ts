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
 * Module: TYPO3/CMS/Filelist/RenameFile
 * Modal to pick the required conflict strategy for colliding filenames
 * @exports TYPO3/CMS/Filelist/RenameFile
 */
class FileSearch {
  constructor() {
    $((): void => {
      const $searchFields = $('input[name="tx_filelist_file_filelistlist[searchWord]"]');
      const searchResultShown = ('' !== $searchFields.first().val());

      // make search field clearable
      $searchFields.clearable({
        onClear: (): void => {
          if (searchResultShown) {
            $searchFields.closest('form').submit();
          }
        },
      });
    });
  }
}

export = new FileSearch();
