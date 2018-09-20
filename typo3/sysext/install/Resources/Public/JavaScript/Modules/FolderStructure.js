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
 * Module: TYPO3/CMS/Install/FolderStructure
 */
define(['jquery',
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
    selectorGridderBadge: '.t3js-folderStructure-badge',
    selectorFixTrigger: '.t3js-folderStructure-errors-fix',
    selectorOutputContainer: '.t3js-folderStructure-output',
    selectorErrorContainer: '.t3js-folderStructure-errors',
    selectorErrorList: '.t3js-folderStructure-errors-list',
    selectorErrorFixTrigger: '.t3js-folderStructure-errors-fix',
    selectorOkContainer: '.t3js-folderStructure-ok',
    selectorOkList: '.t3js-folderStructure-ok-list',
    selectorPermissionContainer: '.t3js-folderStructure-permissions',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;

      // Get status on initialize to have the badge and content ready
      this.getStatus();

      currentModal.on('click', this.selectorErrorFixTrigger, function(e) {
        e.preventDefault();
        self.fix();
      });
    },

    getStatus: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $errorBadge = $(this.selectorGridderBadge);
      $errorBadge.text('').hide();
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      modalContent.find(self.selectorOutputContainer).empty().append(message);
      $.ajax({
        url: Router.getUrl('folderStructureGetStatus'),
        cache: false,
        success: function(data) {
          modalContent.empty().append(data.html);
          if (data.success === true && Array.isArray(data.errorStatus)) {
            var errorCount = 0;
            if (data.errorStatus.length > 0) {
              modalContent.find(self.selectorErrorContainer).show();
              modalContent.find(self.selectorErrorList).empty();
              data.errorStatus.forEach((function(element) {
                errorCount += 1;
                $errorBadge.text(errorCount).show();
                var message = InfoBox.render(element.severity, element.title, element.message);
                modalContent.find(self.selectorErrorList).append(message);
              }));
            } else {
              modalContent.find(self.selectorErrorContainer).hide();
            }
          }
          if (data.success === true && Array.isArray(data.okStatus)) {
            if (data.okStatus.length > 0) {
              modalContent.find(self.selectorOkContainer).show();
              modalContent.find(self.selectorOkList).empty();
              data.okStatus.forEach((function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                modalContent.find(self.selectorOkList).append(message);
              }));
            } else {
              modalContent.find(self.selectorOkContainer).hide();
            }
          }
          var element = data.folderStructureFilePermissionStatus;
          message = InfoBox.render(element.severity, element.title, element.message);
          modalContent.find(self.selectorPermissionContainer).empty().append(message);
          element = data.folderStructureDirectoryPermissionStatus;
          message = InfoBox.render(element.severity, element.title, element.message);
          modalContent.find(self.selectorPermissionContainer).append(message);
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    fix: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $outputContainer = this.currentModal.find(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().html(message);
      $.ajax({
        url: Router.getUrl('folderStructureFix'),
        cache: false,
        success: function(data) {
          self.removeLoadingMessage($outputContainer);
          if (data.success === true && Array.isArray(data.fixedStatus)) {
            if (data.fixedStatus.length > 0) {
              data.fixedStatus.forEach(function(element) {
                message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            } else {
              message = InfoBox.render(Severity.warning, 'Nothing fixed', '');
              $outputContainer.append(message);
            }
            self.getStatus();
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    removeLoadingMessage: function($container) {
      $container.find('.alert-loading').remove();
    }
  };
});
