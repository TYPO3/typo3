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

import ElementBrowser from './element-browser';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/recordlist/browse-database
 * Database record selection
 * @exports @typo3/recordlist/browse-database
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
  }
}

export default new BrowseDatabase();
