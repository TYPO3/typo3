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
 * Module: TYPO3/CMS/Recordlist/PageLinkHandler
 * @exports TYPO3/CMS/Recordlist/PageLinkHandler
 * Page link interaction
 */
class PageLinkHandler {
  private currentLink: string = '';

  constructor() {
    $((): void => {
      this.currentLink = $('body').data('currentLink');
      $('a.t3js-pageLink').on('click', this.linkPage);
      $('input.t3js-linkCurrent').on('click', this.linkCurrent);
      $('input.t3js-pageLink').on('click', this.linkPageByTextfield);
    });
  }

  /**
   * @param {JQueryEventObject} event
   */
  public linkPage = (event: JQueryEventObject): void => {
    event.preventDefault();
    LinkBrowser.finalizeFunction($(event.currentTarget).attr('href'));
  }

  /**
   * @param {JQueryEventObject} event
   */
  public linkPageByTextfield = (event: JQueryEventObject): void => {
    event.preventDefault();

    let value = $('#luid').val();
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

  /**
   * @param {JQueryEventObject} event
   */
  public linkCurrent = (event: JQueryEventObject): void => {
    event.preventDefault();
    LinkBrowser.finalizeFunction(this.currentLink);
  }
}

export = new PageLinkHandler();
