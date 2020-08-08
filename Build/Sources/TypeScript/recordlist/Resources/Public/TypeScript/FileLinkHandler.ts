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
// Yes we really need this import, because Tree... is used in inline markup...
import Tree = require('TYPO3/CMS/Backend/LegacyTree');

/**
 * Module: TYPO3/CMS/Recordlist/FileLinkHandler
 * File link interaction
 * @exports TYPO3/CMS/Recordlist/FileLinkHandler
 */
class FileLinkHandler {
  currentLink: string = '';

  constructor() {
    // until we use onclick attributes, we need the Tree component
    Tree.noop();
    $(() => {
      this.currentLink = $('body').data('currentLink');
      $('a.t3js-fileLink').on('click', this.linkFile);
      $('input.t3js-linkCurrent').on('click', this.linkCurrent);
    });
  }

  public linkFile = (event: JQueryEventObject): void => {
    event.preventDefault();
    LinkBrowser.finalizeFunction($(event.currentTarget).attr('href'));
  }

  public linkCurrent = (event: JQueryEventObject): void => {
    event.preventDefault();
    LinkBrowser.finalizeFunction(this.currentLink);
  }
}

export = new FileLinkHandler();
