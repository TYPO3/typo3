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
 * Module: TYPO3/CMS/Recordlist/TelephoneLinkHandler
 * @exports TYPO3/CMS/Recordlist/TelephoneLinkHandler
 * Telephone link interaction
 */
class TelephoneLinkHandler {
  constructor() {
    new RegularEvent('submit', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      const inputField = targetEl.querySelector('[name="ltelephone"]') as HTMLInputElement;
      let value = inputField.value;
      if (value === 'tel:') {
        return;
      }
      if (value.startsWith('tel:')) {
        value = value.substr(4);
      }

      LinkBrowser.finalizeFunction('tel:' + value);
    }).delegateTo(document, '#ltelephoneform');
  }
}

export = new TelephoneLinkHandler();
