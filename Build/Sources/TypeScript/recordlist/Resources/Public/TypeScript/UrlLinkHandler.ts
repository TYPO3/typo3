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
import LinkBrowser = require('./LinkBrowser');

/**
 * Module: TYPO3/CMS/Recordlist/UrlLinkHandler
 * @exports TYPO3/CMS/Recordlist/UrlLinkHandler
 * URL link interaction
 */
class UrlLinkHandler {
  constructor() {
    $((): void => {
      $('#lurlform').on('submit', this.link);
    });
  }

  public link = (event: JQueryEventObject): void => {
    event.preventDefault();

    const value = $(event.currentTarget).find('[name="lurl"]').val();
    if (value === '') {
      return;
    }
    LinkBrowser.finalizeFunction(value);
  }
}

export = new UrlLinkHandler();
