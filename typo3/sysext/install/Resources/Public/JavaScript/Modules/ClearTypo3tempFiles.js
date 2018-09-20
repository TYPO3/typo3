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
 * Module: TYPO3/CMS/Install/ClearTypo3tempFiles
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
    selectorModuleContent: '.t3js-module-content',
    selectorDeleteTrigger: '.t3js-clearTypo3temp-delete',
    selectorOutputContainer: '.t3js-clearTypo3temp-output',
    selectorStatContainer: '.t3js-clearTypo3temp-stat-container',
    selectorStatsTrigger: '.t3js-clearTypo3temp-stats',
    selectorStatTemplate: '.t3js-clearTypo3temp-stat-template',
    selectorStatDescription: '.t3js-clearTypo3temp-stat-description',
    selectorStatNumberOfFiles: '.t3js-clearTypo3temp-stat-numberOfFiles',
    selectorStatDirectory: '.t3js-clearTypo3temp-stat-directory',
    selectorStatName: '.t3js-clearTypo3temp-stat-name',
    selectorStatLastRuler: '.t3js-clearTypo3temp-stat-lastRuler',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getStats();

      currentModal.on('click', this.selectorStatsTrigger, function(e) {
        e.preventDefault();
        $(self.selectorOutputContainer).empty();
        self.getStats();
      });
      currentModal.on('click', this.selectorDeleteTrigger, function(e) {
        var folder = $(this).data('folder');
        var storageUid = $(this).data('storage-uid');
        e.preventDefault();
        self.delete(folder, storageUid);
      });
    },

    getStats: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('clearTypo3tempFilesStats'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach(function(element) {
                if (element.numberOfFiles > 0) {
                  var aStat = modalContent.find(self.selectorStatTemplate).clone();
                  aStat.find(self.selectorStatNumberOfFiles).text(element.numberOfFiles);
                  aStat.find(self.selectorStatDirectory).text(element.directory);
                  aStat.find(self.selectorDeleteTrigger).attr('data-folder', element.directory);
                  aStat.find(self.selectorDeleteTrigger).attr('data-storage-uid', element.storageUid);
                  modalContent.find(self.selectorStatContainer).append(aStat.html());
                }
              });
            }
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    delete: function(folder, storageUid) {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('clear-typo3temp-delete-token');
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        context: this,
        data: {
          'install': {
            'action': 'clearTypo3tempFiles',
            'token': executeToken,
            'folder': folder,
            'storageUid': storageUid
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              Notification.success(element.message);
            });
            this.getStats();
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
