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

import LinkBrowser from './link-browser';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/recordlist/record-link-handler
 * record link interaction
 */
class RecordLinkHandler {
  constructor() {
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const data = targetEl.closest('span').dataset;
      LinkBrowser.finalizeFunction(document.body.dataset.identifier + data.uid);
    }).delegateTo(document, '[data-close]');
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      LinkBrowser.finalizeFunction(document.body.dataset.currentLink);
    }).delegateTo(document, 'input.t3js-linkCurrent');
  }
}

export default new RecordLinkHandler();
