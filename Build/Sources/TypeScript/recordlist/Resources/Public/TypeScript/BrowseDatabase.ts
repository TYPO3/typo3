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

/**
 * Module: TYPO3/CMS/Recordlist/BrowseDatabase
 * Database record selection
 * @exports TYPO3/CMS/Recordlist/BrowseDatabase
 */
class BrowseDatabase {
  constructor() {
    $((): void => {
      $('[data-close]').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        const data = $(event.currentTarget).parents('span').data();

        ElementBrowser.insertElement(
          data.table,
          data.uid,
          'db',
          data.title,
          '',
          '',
          data.icon,
          '',
          parseInt($(event.currentTarget).data('close'), 10) === 1,
        );
      });
    });

    // adjust searchbox layout
    const searchbox: HTMLElement = document.getElementById('db_list-searchbox-toolbar');
    searchbox.style.display = 'block';
    searchbox.style.position = 'relative';
  }
}

export = new BrowseDatabase();
