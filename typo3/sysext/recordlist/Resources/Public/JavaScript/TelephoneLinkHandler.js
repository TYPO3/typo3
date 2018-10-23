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
 * Module: TYPO3/CMS/Recordlist/TelephoneLinkHandler
 * Telephone link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
  'use strict';

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Recordlist/TelephoneLinkHandler
   */
  var TelephoneLinkHandler = {};

  $(function() {
    $('#ltelephoneform').on('submit', function(event) {
      event.preventDefault();

      var value = $(this).find('[name="ltelephone"]').val();
      if (value === 'tel:') {
        return;
      }
      if (value.indexOf('tel:') === 0) {
        value = value.substr(4);
      }

      LinkBrowser.finalizeFunction('tel:' + value);
    });
  });

  return TelephoneLinkHandler;
});
