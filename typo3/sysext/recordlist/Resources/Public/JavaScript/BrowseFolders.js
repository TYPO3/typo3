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

/**
 * Module: TYPO3/CMS/Recordlist/BrowseFolders
 * Folder selection
 * @exports TYPO3/CMS/Recordlist/BrowseFolders
 */
define(['jquery', 'TYPO3/CMS/Recordlist/ElementBrowser', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, ElementBrowser, Modal, Severity) {
  'use strict';

  $(function() {
    $('[data-folder-id]').on('click', function(event) {
      event.preventDefault();
      var folderId = $(this).data('folderId');
      var close = $(this).data('close');
      ElementBrowser.insertElement('', folderId, 'folder', folderId, folderId, '', '', '', close);
    });

    $('.t3js-folderIdError').on('click', function(event) {
      event.preventDefault();
      Modal.confirm('', $(this).data('message'), Severity.error, [], []);
    });
  });

});
