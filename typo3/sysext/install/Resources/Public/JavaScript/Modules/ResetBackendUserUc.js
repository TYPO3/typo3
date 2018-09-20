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
 * Module: TYPO3/CMS/Install/DumpAutoload
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification) {
  'use strict';

  return {
    selectorOutputContainer: '.t3js-resetBackendUserUc-output',

    initialize: function($trigger) {
      $.ajax({
        url: Router.getUrl('resetBackendUserUc'),
        cache: false,
        beforeSend: function() {
          $trigger.addClass('disabled');
        },
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              data.status.forEach(function(element) {
                Notification.success(element.message);
              });
            }
          } else {
            Notification.error('Something went wrong ...');
          }
        },
        error: function(xhr) {
          // If reset fails on server side (typically a 500), do not crash entire install tool
          // but render an error notification instead.
          Notification.error('Resetting backend user uc failed. Please check the system for missing database fields and try again.');
        },
        complete: function() {
          $trigger.removeClass('disabled');
        }
      });
    }
  };
});
