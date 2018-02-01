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
 * Module: TYPO3/CMS/Recordlist/MailLinkHandler
 * Mail link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
  'use strict';

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Recordlist/MailLinkHandler
   */
  var MailLinkHandler = {};

  $(function() {
    $('#lmailform').on('submit', function(event) {
      event.preventDefault();

      var value = $(this).find('[name="lemail"]').val();
      if (value === 'mailto:') {
        return;
      }

      while (value.substr(0, 7) === 'mailto:') {
        value = value.substr(7);
      }

      LinkBrowser.finalizeFunction('mailto:' + value);
    });
  });

  return MailLinkHandler;
});
