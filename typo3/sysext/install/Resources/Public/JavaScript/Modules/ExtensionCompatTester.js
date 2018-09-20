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
    selectorModuleContent: '.t3js-module-content',
    selectorCheckTrigger: '.t3js-extensionCompatTester-check',
    selectorUninstallTrigger: '.t3js-extensionCompatTester-uninstall',
    selectorOutputContainer: '.t3js-extensionCompatTester-output',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getLoadedExtensionList();

      currentModal.on('click', this.selectorCheckTrigger, function(e) {
        currentModal.find(self.selectorUninstallTrigger).hide();
        currentModal.find(self.selectorOutputContainer).empty();
        self.getLoadedExtensionList();
      });
      currentModal.on('click', this.selectorUninstallTrigger, function(e) {
        self.uninstallExtension($(e.target).data('extension'));
      });
    },

    getLoadedExtensionList: function () {
      const self = this;
      this.currentModal.find(this.selectorCheckTrigger).prop('disabled', true);

      this.currentModal.find('.modal-loading').hide();
      const modalContent = this.currentModal.find(this.selectorModalBody);
      const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
      const message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);

      $.ajax({
        url: Router.getUrl('extensionCompatTesterLoadedExtensionList'),
        cache: false,
        success: function (data) {
          modalContent.empty().append(data.html);

          const $outputContainer = self.currentModal.find(self.selectorOutputContainer);
          const progressBar = ProgressBar.render(Severity.loading, 'Loading...', '');
          $outputContainer.append(progressBar);

          if (data.success === true && Array.isArray(data.extensions)) {
            const loadExtLocalconf = function () {
              const promises = [];
              data.extensions.forEach(function (extension) {
                promises.push(self.loadExtLocalconf(extension));
              });
              return $.when.apply($, promises).done(function () {
                const message = InfoBox.render(Severity.OK, 'ext_localconf.php of all loaded extensions successfully loaded', '');
                $outputContainer.append(message);
              });
            };

            const loadExtTables = function () {
              const promises = [];
              data.extensions.forEach(function (extension) {
                promises.push(self.loadExtTables(extension));
              });
              return $.when.apply($, promises).done(function () {
                const message = InfoBox.render(Severity.OK, 'ext_tables.php of all loaded extensions successfully loaded', '');
                $outputContainer.append(message);
              });
            };

            $.when(loadExtLocalconf(), loadExtTables()).fail(function (response) {
              const message = InfoBox.render(Severity.error, 'Loading ' + response.scope + ' of extension "' + response.extension + '" failed');
              $outputContainer.append(message);
              modalContent.find(self.selectorUninstallTrigger).text('Unload extension "' + response.extension + '"').attr('data-extension', response.extension).show();
            }).always(function () {
              $outputContainer.find('.alert-loading').remove();
              self.currentModal.find(self.selectorCheckTrigger).prop('disabled', false);
            });
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function (xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    loadExtLocalconf: function(extension) {
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_localconf-token');
      var $ajax = $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtLocalconf',
            'token': executeToken,
            'extension': extension
          }
        }
      });

      return $ajax.promise().then(null, function() {
        throw {
          scope: 'ext_localconf.php',
          extension: extension
        };
      });
    },

    loadExtTables: function(extension) {
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_tables-token');
      var $ajax = $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtTables',
            'token': executeToken,
            'extension': extension
          }
        }
      });

      return $ajax.promise().then(null, function() {
        throw {
          scope: 'ext_tables.php',
          extension: extension
        };
      });
    },

    /**
     * Send an ajax request to uninstall an extension (or multiple extensions)
     *
     * @param extension string of extension(s) - may be comma separated
     */
    uninstallExtension: function(extension) {
      var self = this;
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-uninstall-extension-token');
      var modalContent = this.currentModal.find(this.selectorModalBody);
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
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
