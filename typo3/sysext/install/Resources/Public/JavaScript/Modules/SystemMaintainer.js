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
 * Module: TYPO3/CMS/Install/SystemMaintainer
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'bootstrap',
  'chosen'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
  'use strict';

  return {
    selectorGridderOpener: 't3js-systemMaintainer-open',
    selectorWriteTrigger: '.t3js-systemMaintainer-write',
    selectorWriteToken: '#t3js-systemMaintainer-write-token',
    selectorOutputContainer: '.t3js-systemMaintainer-output',
    selectorChosenContainer: '.t3js-systemMaintainer-chosen',
    selectorChosenField: '.t3js-systemMaintainer-chosen-select',

    initialize: function() {
      var self = this;

      // Get current system maintainer list on card open
      $(document).on('cardlayout:card-opened', function(event, $card) {
        if ($card.hasClass(self.selectorGridderOpener)) {
          self.getList();
        }
      });

      $(document).on('click', this.selectorWriteTrigger, function(e) {
        e.preventDefault();
        self.write();
      });
    },

    getList: function() {
      var self = this;
      var $chosenContainer = $(this.selectorChosenContainer);
      var $outputContainer = $(this.selectorOutputContainer);
      var $chosenField = $(self.selectorChosenField);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().append(message);
      $chosenContainer.hide();
      $chosenField.empty();
      $.ajax({
        url: Router.getUrl('systemMaintainerGetList'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.find('.alert-loading').remove();
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            }
            if (Array.isArray(data.users)) {
              data.users.forEach(function(element) {
                var name = element.username;
                if (element.disable) {
                  name = '[DISABLED] ' + name;
                }
                var selected = '';
                if (element.isSystemMaintainer) {
                  selected = 'selected="selected"';
                }
                $chosenField.append(
                  '<option value="' + element.uid + '" ' + selected + '>' + name + '</option>'
                );
              });
            }
            var config = {
              '.chosen-select': {width: "100%", placeholder_text_multiple: "users"},
              '.chosen-select-deselect': {allow_single_deselect: true},
              '.chosen-select-width': {width: "100%"}
            };
            for (var selector in config) {
              $(selector).chosen(config[selector]);
            }
            $chosenContainer.show();
            $chosenField.trigger('chosen:updated');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    write: function() {
      var $outputContainer = $(this.selectorOutputContainer);
      var selectedUsers = $(this.selectorChosenField).val();
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        data: {
          'install': {
            'users': selectedUsers,
            'token': $(this.selectorWriteToken).text(),
            'action': 'systemMaintainerWrite'
          }
        },
        success: function(data) {
          if (data.success === true) {
            $outputContainer.find('.alert-loading').remove();
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.empty().append(message);
              });
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
    }
  };
});
