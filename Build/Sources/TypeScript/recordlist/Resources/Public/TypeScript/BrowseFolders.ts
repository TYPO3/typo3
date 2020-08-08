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
import ElementBrowser = require('./ElementBrowser');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Severity = require('TYPO3/CMS/Backend/Severity');

/**
 * Module: TYPO3/CMS/Recordlist/BrowseFolders
 * Folder selection
 * @exports TYPO3/CMS/Recordlist/BrowseFolders
 */
class BrowseFolders {
  constructor() {
    $(() => {
      $('[data-folder-id]').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        const $element: JQuery = $(event.currentTarget);
        const folderId = $element.data('folderId');
        const close = parseInt($element.data('close'), 10) === 1;
        ElementBrowser.insertElement('', folderId, 'folder', folderId, folderId, '', '', '', close);
      });

      $('.t3js-folderIdError').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        Modal.confirm('', $(event.currentTarget).data('message'), Severity.error, [], []);
      });
    });
  }
}

export = new BrowseFolders();
