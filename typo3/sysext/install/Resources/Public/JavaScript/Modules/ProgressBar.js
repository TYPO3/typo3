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
 * Module: TYPO3/CMS/Install/ProgressBar
 */
define(['jquery', 'TYPO3/CMS/Install/Severity'], function($, Severity) {
  'use strict';

  /**
   * @type {{template: (*)}}
   */
  var ProgressBar = {
    template: $('<div class="progress"><div class="t3js-progressbar progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"> <span></span></div></div>')
  };

  /**
   * render a ProgressBar
   * @param {Number} severity
   * @param {String} title
   * @param {Number} progress
   * @returns {jQuery}
   */
  ProgressBar.render = function(severity, title, progress) {
    var progressBar = this.template.clone();
    progressBar.addClass('progress-bar-' + Severity.getCssClass(severity));
    if (progress) {
      progressBar.css('width', progress + '%');
      progressBar.attr('aria-valuenow', progresss);
    }
    if (title) {
      progressBar.find('span').text(title);
    }
    return progressBar;
  };

  return ProgressBar;
});
