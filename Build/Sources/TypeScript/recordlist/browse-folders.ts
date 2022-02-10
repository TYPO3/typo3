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
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/recordlist/browse-folders
 * Folder selection
 * @exports @typo3/recordlist/browse-folders
 */
class BrowseFolders {
  constructor() {
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const folderId = targetEl.dataset.folderId;
      ElementBrowser.insertElement(
        '',
        folderId,
        folderId,
        folderId,
        parseInt(targetEl.dataset.close || '0', 10) === 1
      );
    }).delegateTo(document, '[data-folder-id]');

    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      Modal.confirm('', targetEl.dataset.message, Severity.error, [], []);
    }).delegateTo(document, '.t3js-folderIdError');
  }
}

export default new BrowseFolders();
