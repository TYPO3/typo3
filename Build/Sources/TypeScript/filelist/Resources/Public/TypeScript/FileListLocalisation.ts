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
 * Module: TYPO3/CMS/Filelist/FileListLocalisation
 * @exports TYPO3/CMS/Filelist/FileListLocalisation
 */
class FileListLocalisation {
  constructor() {
    DocumentService.ready().then((): void => {
      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        const id = target.dataset.fileid;
        document.querySelector('div[data-fileid="' + id + '"]').classList.toggle('hidden');
      }).delegateTo(document, 'a.filelist-translationToggler');
    });
  }
}

export = new FileListLocalisation();
