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
 * Module: TYPO3/CMS/Install/Severity
 */
define([], function() {
  'use strict';

  var Severity = {
    loading: -3,
    notice: -2,
    info: -1,
    ok: 0,
    warning: 1,
    error: 2
  };

  Severity.getCssClass = function(severity) {
    var severityClass;
    switch (severity) {
      case Severity.loading:
        severityClass = 'notice alert-loading';
        break;
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

  return Severity;
});
