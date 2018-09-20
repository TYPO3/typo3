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
 * Module: TYPO3/CMS/Install/TcaExtTablesCheck
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
    selectorModalBody: '.t3js-modal-body',
    selectorCheckTrigger: '.t3js-tcaExtTablesCheck-check',
    selectorOutputContainer: '.t3js-tcaExtTablesCheck-output',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.check();
      currentModal.on('click',  this.selectorCheckTrigger, function(e) {
        e.preventDefault();
        self.check();
      });
    },

    check: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().html(message);
      $.ajax({
        url: Router.getUrl('tcaExtTablesCheck'),
        cache: false,
        success: function(data) {
          modalContent.empty().append(data.html);
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              var message = InfoBox.render(
                Severity.warning,
                'Extensions change TCA in ext_tables.php',
                'Check for ExtensionManagementUtility and $GLOBALS["TCA"]'
              );
              modalContent.find(self.selectorOutputContainer).append(message);
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
                modalContent.append(message);
              });
            } else {
              var message = InfoBox.render(Severity.ok, 'No TCA changes in ext_tables.php files. Good job!', '');
              modalContent.find(self.selectorOutputContainer).append(message);
            }
          } else {
            Notification.error('Something went wrong', 'Use "Check for broken extensions"');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
