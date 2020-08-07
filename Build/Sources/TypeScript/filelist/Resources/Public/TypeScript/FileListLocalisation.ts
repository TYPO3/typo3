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

import $ from 'jquery';

/**
 * Module: TYPO3/CMS/Filelist/FileListLocalisation
 * @exports TYPO3/CMS/Filelist/FileListLocalisation
 */
class FileListLocalisation {
  constructor() {
    $((): void => {
      $('a.filelist-translationToggler').on('click', (event: JQueryEventObject): void => {
        const id = $(event.currentTarget).attr('data-fileid');
        $('div[data-fileid="' + id + '"]').toggle();
      });
    });
  }
}

export = new FileListLocalisation();
