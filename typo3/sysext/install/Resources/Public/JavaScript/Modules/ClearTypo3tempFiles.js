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
  'TYPO3/CMS/Install/Severity'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
  'use strict';

  return {
    selectorGridderOpener: 't3js-clearTypo3temp-open',
    selectorDeleteToken: '#t3js-clearTypo3temp-delete-token',
    selectorDeleteTrigger: '.t3js-clearTypo3temp-delete',
    selectorOutputContainer: '.t3js-clearTypo3temp-output',
    selectorStatContainer: 't3js-clearTypo3temp-stat-container',
    selectorStatsTrigger: '.t3js-clearTypo3temp-stats',
    selectorStatTemplate: '.t3js-clearTypo3temp-stat-template',
    selectorStatDescription: '.t3js-clearTypo3temp-stat-description',
    selectorStatNumberOfFiles: '.t3js-clearTypo3temp-stat-numberOfFiles',
    selectorStatDirectory: '.t3js-clearTypo3temp-stat-directory',
    selectorStatName: '.t3js-clearTypo3temp-stat-name',
    selectorStatLastRuler: '.t3js-clearTypo3temp-stat-lastRuler',


    initialize: function() {
      var self = this;
      // Load stats on first open
      $(document).on('cardlayout:card-opened', function() {
        self.getStats();
      });

      $(document).on('click', this.selectorStatsTrigger, function(e) {
        e.preventDefault();
        $(self.selectorOutputContainer).empty();
        self.getStats();
      });
      $(document).on('click', this.selectorDeleteTrigger, function(e) {
        var folder = $(e.target).data('folder');
        e.preventDefault();
        self.delete(folder);
      });
    },

    getStats: function() {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      var $statContainer = $('.' + this.selectorStatContainer);
      $statContainer.empty();
      var $statTemplate = $(this.selectorStatTemplate);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      $.ajax({
        url: Router.getUrl('clearTypo3tempFilesStats'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.find('.alert-loading').remove();
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach(function(element) {
                if (element.numberOfFiles > 0) {
                  var $aStat = $statTemplate.clone();
                  $aStat.find(self.selectorStatNumberOfFiles).text(element.numberOfFiles);
                  $aStat.find(self.selectorStatDirectory).text(element.directory);
                  $aStat.find(self.selectorDeleteTrigger).data('folder', element.directory);
                  $statContainer.append($aStat);
                }
              });
              $statContainer.find(self.selectorStatLastRuler + ':last').remove();
            }
          } else {
            var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            $outputContainer.append(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    delete: function(folder) {
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().append(message);
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        context: this,
        data: {
          'install': {
            'action': 'clearTypo3tempFiles',
            'token': $(this.selectorDeleteToken).text(),
            'folder': folder
          }
        },
        cache: false,
        success: function(data) {
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.html(message);
            });
            this.getStats();
          } else {
            var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().html(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    }
  };
});
