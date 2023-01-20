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
 * Module: @typo3/backend/mail-link-handler
 * @exports @typo3/backend/mail-link-handler
 * Mail link interaction
 */
class MailLinkHandler {
  constructor() {
    new RegularEvent('submit', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const inputField = targetEl.querySelector('[name="lemail"]') as HTMLInputElement;
      const value = inputField.value;
      const params = new URLSearchParams();
      for (const elementName of ['subject', 'cc', 'bcc', 'body']) {
        const element = targetEl.querySelector('[data-mailto-part="' + elementName + '"]') as HTMLInputElement|null;
        if (element?.value.length) {
          params.set(elementName, encodeURIComponent(element.value));
        }
      }
      let mailtoLink = 'mailto:' + value;
      if ([...params].length > 0) {
        mailtoLink += '?' + params.toString();
      }

      LinkBrowser.finalizeFunction(mailtoLink);
    }).delegateTo(document, '#lmailform');
  }
}

export default new MailLinkHandler();
