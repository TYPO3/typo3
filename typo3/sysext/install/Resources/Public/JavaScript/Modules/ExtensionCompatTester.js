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
 * Module: TYPO3/CMS/Install/ExtensionCompatTester
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Install/Cache'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Cache) {
  'use strict';

  return {
    selectorLoadExtLocalconfToken: '#t3js-extensionCompatTester-loadExtLocalconf-token',
    selectorLoadExtTablesToken: '#t3js-extensionCompatTester-loadExtTables-token',
    selectorUninstallExtensionToken: '#t3js-extensionCompatTester-uninstallExtension-token',
    selectorCheckTrigger: '.t3js-extensionCompatTester-check',
    selectorUninstallTrigger: '.t3js-extensionCompatTester-uninstall',
    selectorOutputContainer: '.t3js-extensionCompatTester-output',

    initialize: function() {
      var self = this;
      $(document).on('click', this.selectorCheckTrigger, function(e) {
        $(self.selectorUninstallTrigger).hide();
        $(self.selectorOutputContainer).empty();
        self.getLoadedExtensionList();
      });
      $(document).on('click', this.selectorUninstallTrigger, function(e) {
        self.uninstallExtension($(e.target).data('extension'));
      });
    },

    getLoadedExtensionList: function() {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      var $uninstallButton = $(this.selectorUninstallTrigger);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      var loadResult = false;
      $.ajax({
        url: Router.getUrl('extensionCompatTesterLoadedExtensionList'),
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.extensions)) {
            var extension;
            try {
              data.extensions.forEach(function(extension) {
                loadResult = self.loadExtLocalconf(extension);
                if (loadResult === false) {
                  throw extension;
                }
              });
              message = InfoBox.render(Severity.OK, 'ext_localconf.php of all loaded extensions successfully loaded', '');
              $outputContainer.find('.alert-loading').remove();
              $outputContainer.append(message);
              var message = ProgressBar.render(Severity.loading, 'Loading...', '');
              $outputContainer.append(message);
              try {
                data.extensions.forEach(function(extension) {
                  loadResult = self.loadExtTables(extension);
                  if (loadResult === false) {
                    throw extension;
                  }
                });
                message = InfoBox.render(Severity.OK, 'ext_tables.php of all loaded extensions successfully loaded', '');
                $outputContainer.find('.alert-loading').remove();
                $outputContainer.append(message);
              } catch (extension) {
                message = InfoBox.render(Severity.error, 'Loading ext_tables.php of extension "' + extension + '" failed');
                $outputContainer.find('.alert-loading').remove();
                $outputContainer.append(message);
                $uninstallButton.text('Unload extension "' + extension + '"').data('extension', extension).show();
              }
            } catch (extension) {
              message = InfoBox.render(Severity.error, 'Loading ext_localconf.php of extension "' + extension + '" failed');
              $outputContainer.find('.alert-loading').remove();
              $outputContainer.append(message);
              $uninstallButton.text('Unload extension "' + extension + '"').data('extension', extension).show();
            }
          } else {
            message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().html(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    loadExtLocalconf: function(extension) {
      var self = this;
      var result = false;
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        async: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtLocalconf',
            'token': $(self.selectorLoadExtLocalconfToken).text(),
            'extension': extension
          }
        },
        success: function(data) {
          result = data.success === true;
        },
        error: function(data) {
          result = false;
        }
      });
      return result;
    },

    loadExtTables: function(extension) {
      var self = this;
      var result = false;
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        async: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtTables',
            'token': $(self.selectorLoadExtTablesToken).text(),
            'extension': extension
          }
        },
        success: function(data) {
          result = data.success === true;
        },
        error: function(data) {
          result = false;
        }
      });
      return result;
    },

    /**
     * Send an ajax request to uninstall an extension (or multiple extensions)
     *
     * @param extension string of extension(s) - may be comma separated
     */
    uninstallExtension: function(extension) {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      $.ajax({
        url: Router.getUrl(),
        cache: false,
        method: 'POST',
        data: {
          'install': {
            'action': 'extensionCompatTesterUninstallExtension',
            'token': $(self.selectorUninstallExtensionToken).text(),
            'extension': extension
          }
        },
        success: function(data) {
          if (data.success) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.empty().append(message);
              });
            }
            $(self.selectorUninstallTrigger).hide();
            Cache.clearAll();
            self.getLoadedExtensionList();
          } else {
            message = InfoBox.render(Severity.error, 'Something went wrong', '');
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
