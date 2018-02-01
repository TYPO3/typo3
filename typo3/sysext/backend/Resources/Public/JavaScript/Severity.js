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
 * Module: TYPO3/CMS/Backend/Severity
 * Severity for the TYPO3 backend
 */
define(function() {
  'use strict';

  try {
    // fetch from opening window
    if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Severity) {
      return window.opener.TYPO3.Severity;
    }

    // fetch from parent
    if (parent && parent.window.TYPO3 && parent.window.TYPO3.Severity) {
      return parent.window.TYPO3.Severity;
    }

    // fetch object from outer frame
    if (top && top.TYPO3 && top.TYPO3.Severity) {
      return top.TYPO3.Severity;
    }
  } catch (e) {
    // This only happens if the opener, parent or top is some other url (eg a local file)
    // which loaded the current window. Then the browser's cross domain policy jumps in
    // and raises an exception.
    // For this case we are safe and we can create our global object below.
  }

  /**
   * Severity object
   *
   * @type {{notice: number, information: number, info: number, ok: number, warning: number, error: number}}
   * @exports TYPO3/CMS/Backend/Severity
   */
  var Severity = {
    notice: -2,
    // @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 9, use info instead of information
    information: -1,
    info: -1,
    ok: 0,
    warning: 1,
    error: 2
  };

  /**
   * Gets the CSS class for the severity
   *
   * @param {Number} severity
   * @returns {String}
   */
  Severity.getCssClass = function(severity) {
    var severityClass;
    switch (severity) {
      case Severity.notice:
        severityClass = 'notice';
        break;
      case Severity.ok:
        severityClass = 'success';
        break;
      case Severity.warning:
        severityClass = 'warning';
        break;
      case Severity.error:
        severityClass = 'danger';
        break;
      case Severity.info:
      default:
        severityClass = 'info';
    }
    return severityClass;
  };

  // attach to global frame
  TYPO3.Severity = Severity;

  return Severity;
});
