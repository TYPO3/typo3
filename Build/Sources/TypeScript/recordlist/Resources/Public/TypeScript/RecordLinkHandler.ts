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

import LinkBrowser = require('./LinkBrowser');
import RegularEvent from 'TYPO3/CMS/Core/Event/RegularEvent';

/**
 * Module: TYPO3/CMS/Recordlist/RecordLinkHandler
 * record link interaction
 */
class RecordLinkHandler {
  private currentLink: string = '';
  private identifier: string = '';

  constructor() {
    this.currentLink = document.body.dataset.currentLink;
    this.identifier = document.body.dataset.identifier;

    // adjust searchbox layout
    const searchbox: HTMLElement = document.getElementById('db_list-searchbox-toolbar');
    searchbox.style.display = 'block';
    searchbox.style.position = 'relative';

    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const data = targetEl.closest('span').dataset;
      LinkBrowser.finalizeFunction(this.identifier + data.uid);
    }).delegateTo(document, '[data-close]');
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      LinkBrowser.finalizeFunction(this.currentLink);
    }).delegateTo(document, 'input.t3js-linkCurrent');
  }
}

export = new RecordLinkHandler();
