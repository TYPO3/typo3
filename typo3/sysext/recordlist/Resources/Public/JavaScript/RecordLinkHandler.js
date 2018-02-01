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

/**
 * Module: TYPO3/CMS/Recordlist/RecordLinkHandler
 * record link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
  'use strict';

  /**
   * @type {{currentLink: string, identifier: string, linkRecord: function, linkCurrent: function}}
   */
  var RecordLinkHandler = {
    currentLink: '',
    identifier: '',

    /**
     * @param {Event} event
     */
    linkRecord: function(event) {
      event.preventDefault();

      var data = $(this).parents('span').data();
      LinkBrowser.finalizeFunction(RecordLinkHandler.identifier + data.uid);
    },

    /**
     * @param {Event} event
     */
    linkCurrent: function(event) {
      event.preventDefault();

      LinkBrowser.finalizeFunction(RecordLinkHandler.currentLink);
    }
  };

  $(function() {
    var body = $('body');
    RecordLinkHandler.currentLink = body.data('currentLink');
    RecordLinkHandler.identifier = body.data('identifier');

    // adjust searchbox layout
    var searchbox = document.getElementById('db_list-searchbox-toolbar');
    searchbox.style.display = 'block';
    searchbox.style.position = 'relative';

    $('[data-close]').on('click', RecordLinkHandler.linkRecord);
    $('input.t3js-linkCurrent').on('click', RecordLinkHandler.linkCurrent);
  });

  return RecordLinkHandler;
});
