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

import * as $ from 'jquery';

/**
 * Module: TYPO3/CMS/Scheduler/PageBrowser
 * Javascript for adding links for calling the page browser pop up
 */
class PageBrowser {
  constructor() {
    $(document).on('click', '.t3js-pageBrowser', (evt: JQueryEventObject): void => {
      let browserWin: Window;
      let pageUrl: string = $(evt.currentTarget).data('url');
      browserWin = window.open(pageUrl, 'Typo3WinBrowser', 'height=650,width=800,status=0,menubar=0,resizable=1,scrollbars=1');
      browserWin.focus();
    });
  }
}

export = new PageBrowser();
