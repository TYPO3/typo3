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
 * Module: TYPO3/CMS/Install/ClearTable
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
    selectorClearTrigger: '.t3js-clearTables-clear',
    selectorStatsTrigger: '.t3js-clearTables-stats',
    selectorOutputContainer: '.t3js-clearTables-output',
    selectorStatContainer: '.t3js-clearTables-stat-container',
    selectorStatTemplate: '.t3js-clearTables-stat-template',
    selectorStatDescription: '.t3js-clearTables-stat-description',
    selectorStatRows: '.t3js-clearTables-stat-rows',
    selectorStatName: '.t3js-clearTables-stat-name',
    selectorStatLastRuler: '.t3js-clearTables-stat-lastRuler',
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

      currentModal.on('click', this.selectorClearTrigger, function(e) {
        var table = $(e.target).closest(self.selectorClearTrigger).data('table');
        e.preventDefault();
        self.clear(table);
      });
    },

    getStats: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('clearTablesStats'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach(function(element) {
                if (element.rowCount > 0) {
                  var aStat = modalContent.find(self.selectorStatTemplate).clone();
                  aStat.find(self.selectorStatDescription).text(element.description);
                  aStat.find(self.selectorStatName).text(element.name);
                  aStat.find(self.selectorStatRows).text(element.rowCount);
                  aStat.find(self.selectorClearTrigger).attr('data-table', element.name);
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

    clear: function(table) {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('clear-tables-clear-token');
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        context: this,
        data: {
          'install': {
            'action': 'clearTablesClear',
            'token': executeToken,
            'table': table
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              Notification.success(element.message);
            });
          } else {
            Notification.error('Something went wrong');
          }
          this.getStats();
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
