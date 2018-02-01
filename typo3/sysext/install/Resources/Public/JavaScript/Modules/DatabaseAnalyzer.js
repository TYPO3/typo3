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
  'TYPO3/CMS/Install/Severity'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
  'use strict';

  return {
    selectorGridderOpener: 't3js-databaseAnalyzer-open',
    selectorAnalyzeTrigger: '.t3js-databaseAnalyzer-analyze',
    selectorExecuteTrigger: '.t3js-databaseAnalyzer-execute',
    selectorOutputContainer: '.t3js-databaseAnalyzer-output',
    selectorSuggestionBlock: '.t3js-databaseAnalyzer-suggestion-block',
    selectorSuggestionLine: '.t3js-databaseAnalyzer-suggestion-line',

    initialize: function() {
      var self = this;

      // Load main content on first open
      $(document).on('cardlayout:card-opened', function(event, $card) {
        if ($card.hasClass(self.selectorGridderOpener) && !$card.data('isInitialized')) {
          $card.data('isInitialized', true);
          self.analyze();
        }
      });

      // Select / deselect all checkboxes
      $(document).on('click', '.t3js-databaseAnalyzer-suggestion-block-checkbox', function() {
        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
      });
      $(document).on('click', this.selectorAnalyzeTrigger, function(e) {
        e.preventDefault();
        self.analyze();
      });
      $(document).on('click', this.selectorExecuteTrigger, function(e) {
        e.preventDefault();
        self.execute();
      });
    },

    analyze: function() {
      $(this.selectorOutputContainer).empty();
      this.analyzeAjax();
    },

    analyzeAjax: function() {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      var blockTemplate = $(this.selectorSuggestionBlock).html();
      var lineTemplate = $(this.selectorSuggestionLine).html();
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      $(this.selectorExecuteTrigger).prop('disabled', true);
      $(this.selectorAnalyzeTrigger).prop('disabled', true);
      $.ajax({
        url: Router.getUrl('databaseAnalyzerAnalyze'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.find('.alert-loading').remove();
                $outputContainer.append(message);
              });
            }
            if (Array.isArray(data.suggestions)) {
              data.suggestions.forEach(function(element) {
                var aBlock = $(blockTemplate).clone();
                var key = element.key;
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-legend').text(element.label);
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('id', 't3-install-' + key + '-checkbox');
                if (element.enabled) {
                  aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('checked', 'checked');
                }
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-label').attr('for', 't3-install-' + key + '-checkbox');
                element.children.forEach(function(line) {
                  var aLine = $(lineTemplate).clone();
                  var hash = line.hash;
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-checkbox').attr('id', 't3-install-db-' + hash);
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-checkbox').data('hash', hash);
                  if (element.enabled) {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-checkbox').attr('checked', 'checked');
                  }
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-label').attr('for', 't3-install-db-' + hash);
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-statement').text(line.statement);
                  if (line.current !== undefined) {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current-value').text(line.current);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current').show();
                  }
                  if (line.rowCount !== undefined) {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count-value').text(line.rowCount);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count').show();
                  }
                  aBlock.find('.t3js-databaseAnalyzer-suggestion-block-line').append(aLine);
                });
                $outputContainer.append(aBlock);
              });
              $(self.selectorExecuteTrigger).prop('disabled', false);
              $(self.selectorAnalyzeTrigger).prop('disabled', false);
            }
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().html(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    execute: function() {
      var self = this;
      var executeToken = $('#t3js-databaseAnalyzer-execute-token').text();
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      var $outputContainer = $('.t3js-databaseAnalyzer-output');
      var selectedHashes = [];
      $('.gridder-show .t3js-databaseAnalyzer-output .t3js-databaseAnalyzer-suggestion-block-line input:checked').each(function() {
        selectedHashes.push($(this).data('hash'));
      });
      $outputContainer.empty().html(message);
      $(this.selectorExecuteTrigger).prop('disabled', true);
      $(this.selectorAnalyzeTrigger).prop('disabled', true);
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
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.find('.alert-loading').remove();
                $outputContainer.append(message);
              });
            }
          }
          self.analyzeAjax();
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    }
  };
});
