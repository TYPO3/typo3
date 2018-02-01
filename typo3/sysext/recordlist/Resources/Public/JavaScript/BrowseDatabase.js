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
 * Module: TYPO3/CMS/Recordlist/BrowseDatabase
 * Database record selection
 * @exports TYPO3/CMS/Recordlist/BrowseDatabase
 */
define(['jquery', 'TYPO3/CMS/Recordlist/ElementBrowser'], function($, ElementBrowser) {
  'use strict';

  $(function() {
    $('[data-close]').on('click', function(event) {
      event.preventDefault();
      var data = $(this).parents('span').data();

      ElementBrowser.insertElement(
        data.table,
        data.uid,
        'db',
        data.title,
        '',
        '',
        data.icon,
        '',
        $(this).data('close')
      );
    });

    // adjust searchbox layout
    var searchbox = document.getElementById('db_list-searchbox-toolbar');
    searchbox.style.display = 'block';
    searchbox.style.position = 'relative';
  });

});
