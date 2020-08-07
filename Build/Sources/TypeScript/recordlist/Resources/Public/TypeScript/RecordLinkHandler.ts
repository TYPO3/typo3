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
 * Module: TYPO3/CMS/Recordlist/RecordLinkHandler
 * record link interaction
 */
class RecordLinkHandler {
  private currentLink: string = '';
  private identifier: string = '';

  constructor() {
    $((): void => {
      const body = $('body');
      this.currentLink = body.data('currentLink');
      this.identifier = body.data('identifier');

      // adjust searchbox layout
      const searchbox: HTMLElement = document.getElementById('db_list-searchbox-toolbar');
      searchbox.style.display = 'block';
      searchbox.style.position = 'relative';

      $('[data-close]').on('click', this.linkRecord);
      $('input.t3js-linkCurrent').on('click', this.linkCurrent);
    });
  }

  /**
   * @param {JQueryEventObject} event
   */
  public linkRecord = (event: JQueryEventObject): void => {
    event.preventDefault();
    const data = $(event.currentTarget).parents('span').data();
    LinkBrowser.finalizeFunction(this.identifier + data.uid);
  }

  /**
   * @param {JQueryEventObject} event
   */
  public linkCurrent = (event: JQueryEventObject): void => {
    event.preventDefault();
    LinkBrowser.finalizeFunction(this.currentLink);
  }
}

export = new RecordLinkHandler();
