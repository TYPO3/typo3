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
 * Module: TYPO3/CMS/Install/FlashMessage
 */
define(['jquery', 'TYPO3/CMS/Install/Severity'], function($, Severity) {
  'use strict';

  /**
   * @type {{template: (*)}}
   */
  var FlashMessage = {
    template: $('<div class="t3js-message typo3-message alert"><h4></h4><p class="messageText"></p></div>')
  };

  /**
   * render a FlashMessage
   * @param {Number} severity
   * @param {String} title
   * @param {String} message
   * @returns {jQuery}
   */
  FlashMessage.render = function(severity, title, message) {
    var flashMessage = this.template.clone();
    flashMessage.addClass('alert-' + Severity.getCssClass(severity));
    if (title) {
      flashMessage.find('h4').text(title);
    }
    if (message) {
      flashMessage.find('.messageText').text(message);
    } else {
      flashMessage.find('.messageText').remove();
    }
    return flashMessage;
  };

  return FlashMessage;
});
