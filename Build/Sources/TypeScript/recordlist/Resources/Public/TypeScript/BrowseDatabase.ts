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

import ElementBrowser = require('./ElementBrowser');
import RegularEvent from 'TYPO3/CMS/Core/Event/RegularEvent';

/**
 * Module: TYPO3/CMS/Recordlist/BrowseDatabase
 * Database record selection
 * @exports TYPO3/CMS/Recordlist/BrowseDatabase
 */
class BrowseDatabase {
  constructor() {
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const data = targetEl.closest('span').dataset;
      ElementBrowser.insertElement(
        data.table,
        data.uid,
        data.title,
        '',
        parseInt(targetEl.dataset.close || '0', 10) === 1,
      );
    }).delegateTo(document, '[data-close]');

    // adjust searchbox layout
    const searchbox: HTMLElement = document.getElementById('db_list-searchbox-toolbar');
    searchbox.style.display = 'block';
    searchbox.style.position = 'relative';
  }
}

export = new BrowseDatabase();
