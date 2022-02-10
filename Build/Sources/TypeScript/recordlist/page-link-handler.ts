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
 * Module: @typo3/recordlist/page-link-handler
 * @exports @typo3/recordlist/page-link-handler
 * Page link interaction
 */
class PageLinkHandler {
  constructor() {
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      LinkBrowser.finalizeFunction(targetEl.getAttribute('href'));
    }).delegateTo(document, 'a.t3js-pageLink');

    // Link to current page
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      LinkBrowser.finalizeFunction(document.body.dataset.currentLink);
    }).delegateTo(document, 'input.t3js-linkCurrent');

    // Input field
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      this.linkPageByTextfield();
    }).delegateTo(document, 'input.t3js-pageLink');
  }

  private linkPageByTextfield = (): void => {
    const textField = document.getElementById('luid') as HTMLInputElement;
    let value = textField.value;
    if (!value) {
      return;
    }
    // make sure we use proper link syntax if this is an integer only
    const valueAsNumber = parseInt(value, 10);
    if (!isNaN(valueAsNumber)) {
      value = 't3://page?uid=' + valueAsNumber;
    }
    LinkBrowser.finalizeFunction(value);
  }
}

export default new PageLinkHandler();
