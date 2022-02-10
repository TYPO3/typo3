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
 * Module: @typo3/recordlist/url-link-handler
 * @exports @typo3/recordlist/url-link-handler
 * URL link interaction
 */
class UrlLinkHandler {
  constructor() {
    new RegularEvent('submit', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const inputField = targetEl.querySelector('[name="lurl"]') as HTMLInputElement;
      let value = inputField.value;
      if (value === '') {
        return;
      }
      LinkBrowser.finalizeFunction(value);
    }).delegateTo(document, '#lurlform');
  }
}

export default new UrlLinkHandler();
