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
 * Module: TYPO3/CMS/Install/EnvironmentCheck
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification',
  'bootstrap'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorGridderBadge: '.t3js-environmentCheck-badge',
    selectorExecuteTrigger: '.t3js-environmentCheck-execute',
    selectorOutputContainer: '.t3js-environmentCheck-output',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;

      // Get status on initialize to have the badge and content ready
      this.runTests();

      currentModal.on('click', this.selectorExecuteTrigger, function(e) {
        e.preventDefault();
        self.runTests();
      });
    },

    runTests: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $errorBadge = $(this.selectorGridderBadge);
      $errorBadge.text('').hide();
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      modalContent.find(this.selectorOutputContainer).empty().append(message);
      $.ajax({
        url: Router.getUrl('environmentCheckGetStatus'),
        cache: false,
        success: function(data) {
          modalContent.empty().append(data.html);
          var warningCount = 0;
          var errorCount = 0;
          if (data.success === true && typeof(data.status) === 'object') {
            $.each(data.status, function(i, element) {
              if (Array.isArray(element) && element.length > 0) {
                element.forEach(function(aStatus) {
                  if (aStatus.severity === 1) {
                    warningCount += 1;
                  }
                  if (aStatus.severity === 2) {
                    errorCount += 1;
                  }
                  var message = InfoBox.render(aStatus.severity, aStatus.title, aStatus.message);
                  modalContent.find(self.selectorOutputContainer).append(message);
                });
              }
            });
            if (errorCount > 0) {
              $errorBadge.removeClass('label-warning').addClass('label-danger').text(errorCount).show();
            } else if (warningCount > 0) {
              $errorBadge.removeClass('label-error').addClass('label-warning').text(warningCount).show();
            }
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
