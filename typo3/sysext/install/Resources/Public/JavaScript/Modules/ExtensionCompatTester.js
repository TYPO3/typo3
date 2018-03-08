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
  'TYPO3/CMS/Install/Cache',
  'TYPO3/CMS/Backend/Notification'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Cache, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorLoadExtLocalconfToken: '#t3js-extensionCompatTester-loadExtLocalconf-token',
    selectorLoadExtTablesToken: '#t3js-extensionCompatTester-loadExtTables-token',
    selectorUninstallExtensionToken: '#t3js-extensionCompatTester-uninstallExtension-token',
    selectorCheckTrigger: '.t3js-extensionCompatTester-check',
    selectorUninstallTrigger: '.t3js-extensionCompatTester-uninstall',
    selectorOutputContainer: '.t3js-extensionCompatTester-output',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      self.getLoadedExtensionList();

      currentModal.on('click', this.selectorCheckTrigger, function(e) {
        currentModal.find(self.selectorUninstallTrigger).hide();
        currentModal.find(self.selectorOutputContainer).empty();
        self.getLoadedExtensionList();
      });
      currentModal.on('click', this.selectorUninstallTrigger, function(e) {
        self.uninstallExtension($(e.target).data('extension'));
      });
    },

    getLoadedExtensionList: function() {
      var self = this;
      var modalContent = this.currentModal.find(self.selectorModalBody);
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      modalContent.append(message);
      $outputContainer.append(message);
      var loadResult = false;
      $.ajax({
        url: Router.getUrl('extensionCompatTesterLoadedExtensionList'),
        cache: false,
        success: function(data) {
          modalContent.empty().append(data.html);
          if (data.success === true && Array.isArray(data.extensions)) {
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
                modalContent.append(message);
              } catch (extension) {
                message = InfoBox.render(Severity.error, 'Loading ext_tables.php of extension "' + extension + '" failed');
                modalContent.append(message);
                modalContent.find(self.selectorUninstallTrigger).text('Unload extension "' + extension + '"').attr('data-extension', extension).show();
              }
            } catch (extension) {
              message = InfoBox.render(Severity.error, 'Loading ext_localconf.php of extension "' + extension + '" failed');
              // $outputContainer.find('.alert-loading').remove();
              // $outputContainer.append(message);
              modalContent.append(message);
              modalContent.find(self.selectorUninstallTrigger).text('Unload extension "' + extension + '"').attr('data-extension', extension).show();
            }
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    loadExtLocalconf: function(extension) {
      var self = this;
      var executeToken = self.currentModal.find(this.selectorLoadExtLocalconfToken).text();
      var result = false;
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        async: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtLocalconf',
            'token': executeToken,
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
      var executeToken = self.currentModal.find(this.selectorLoadExtTablesToken).text();
      var result = false;
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        async: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtTables',
            'token': executeToken,
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
      var executeToken = self.currentModal.find(self.selectorUninstallExtensionToken).text();
      var modalContent = self.currentModal.find(self.selectorModalBody);
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      console.log('ExtensionCompatTester.js@174', extension, executeToken);
      $.ajax({
        url: Router.getUrl(),
        cache: false,
        method: 'POST',
        data: {
          'install': {
            'action': 'extensionCompatTesterUninstallExtension',
            'token': executeToken,
            'extension': extension
          }
        },
        success: function(data) {
          if (data.success) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                modalContent.find(self.selectorOutputContainer).empty().append(message);
              });
            }
            $(self.selectorUninstallTrigger).hide();
            //Cache.clearAll();
            self.getLoadedExtensionList();
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    }
  };
});
