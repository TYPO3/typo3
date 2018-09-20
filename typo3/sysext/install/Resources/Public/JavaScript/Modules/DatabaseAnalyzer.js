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
 * Module: TYPO3/CMS/Install/DatabaseAnalyzer
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
    selectorAnalyzeTrigger: '.t3js-databaseAnalyzer-analyze',
    selectorExecuteTrigger: '.t3js-databaseAnalyzer-execute',
    selectorOutputContainer: '.t3js-databaseAnalyzer-output',
    selectorSuggestionBlock: '.t3js-databaseAnalyzer-suggestion-block',
    selectorSuggestionList: '.t3js-databaseAnalyzer-suggestion-list',
    selectorSuggestionLine: '.t3js-databaseAnalyzer-suggestion-line',
    selectorSuggestionLineTemplate: '.t3js-databaseAnalyzer-suggestion-line-template',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getData();

      // Select / deselect all checkboxes
      currentModal.on('click', '.t3js-databaseAnalyzer-suggestion-block-checkbox', function(e) {
        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
      });
      currentModal.on('click', this.selectorAnalyzeTrigger, function(e) {
        e.preventDefault();
        self.analyze();
      });
      currentModal.on('click', this.selectorExecuteTrigger, function(e) {
        e.preventDefault();
        self.execute();
      });
    },

    getData: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('databaseAnalyzer'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
            self.analyze();
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    analyze: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var outputContainer = modalContent.find(this.selectorOutputContainer);
      var executeTrigger = modalContent.find(this.selectorExecuteTrigger);
      var analyzeTrigger = modalContent.find(this.selectorAnalyzeTrigger);

      outputContainer.empty().append(ProgressBar.render(Severity.loading, 'Analyzing current database schema...', ''));

      analyzeTrigger.prop('disabled', true);
      executeTrigger.prop('disabled', true);

      outputContainer.on('change', 'input[type="checkbox"]', function() {
        var hasCheckedCheckboxes = outputContainer.find(':checked').length > 0;
        executeTrigger.prop('disabled', !hasCheckedCheckboxes);
      });

      $.ajax({
        url: Router.getUrl('databaseAnalyzerAnalyze'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              outputContainer.find('.alert-loading').remove();
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                outputContainer.append(message);
              });
            }
            if (Array.isArray(data.suggestions)) {
              data.suggestions.forEach(function(element) {
                var aBlock = modalContent.find(self.selectorSuggestionBlock).clone();
                aBlock.removeClass(self.selectorSuggestionBlock.substr(1));
                var key = element.key;
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-legend').text(element.label);
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('id', 't3-install-' + key + '-checkbox');
                if (element.enabled) {
                  aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('checked', 'checked');
                }
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-label').attr('for', 't3-install-' + key + '-checkbox');
                element.children.forEach(function(line) {
                  var aLine = modalContent.find(self.selectorSuggestionLineTemplate).children().clone();
                  var hash = line.hash;
                  var $checkbox = aLine.find('.t3js-databaseAnalyzer-suggestion-line-checkbox');
                  $checkbox.attr('id', 't3-install-db-' + hash).attr('data-hash', hash);
                  if (element.enabled) {
                    $checkbox.attr('checked', 'checked');
                  }
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-label').attr('for', 't3-install-db-' + hash);
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-statement').text(line.statement);
                  if (typeof line.current !== 'undefined') {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current-value').text(line.current);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current').show();
                  }
                  if (typeof line.rowCount !== 'undefined') {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count-value').text(line.rowCount);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count').show();
                  }
                  aBlock.find(self.selectorSuggestionList).append(aLine);
                });
                outputContainer.append(aBlock.html());
              });

              var isInitiallyDisabled = outputContainer.find(':checked').length === 0;
              analyzeTrigger.prop('disabled', false);
              executeTrigger.prop('disabled', isInitiallyDisabled);
            }
            if (data.suggestions.length === 0 && data.status.length === 0) {
              outputContainer.append(InfoBox.render(Severity.ok, 'Database schema is up to date. Good job!', ''));
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

    execute: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('database-analyzer-execute-token');
      var outputContainer = modalContent.find(this.selectorOutputContainer);
      var selectedHashes = [];

      outputContainer.find('.t3js-databaseAnalyzer-suggestion-line input:checked').each(function() {
        selectedHashes.push($(this).data('hash'));
      });
      outputContainer.empty().append(ProgressBar.render(Severity.loading, 'Executing database updates...', ''));
      modalContent.find(this.selectorExecuteTrigger).prop('disabled', true);
      modalContent.find(this.selectorAnalyzeTrigger).prop('disabled', true);

      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'databaseAnalyzerExecute',
            'token': executeToken,
            'hashes': selectedHashes
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                Notification.showMessage(element.title, element.message, element.severity);
              });
            }
          }
          self.analyze();
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
