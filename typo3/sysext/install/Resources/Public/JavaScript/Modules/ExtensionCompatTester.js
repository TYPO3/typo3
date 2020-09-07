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

          if (data.success === true) {
            self.loadExtLocalconf().done(function() {
              $outputContainer.append(
                InfoBox.render(Severity.ok, 'ext_localconf.php of all loaded extensions successfully loaded', '')
              );
              self.loadExtTables().done(function() {
                $outputContainer.append(
                  InfoBox.render(Severity.ok, 'ext_tables.php of all loaded extensions successfully loaded', '')
                );
              }).fail(function(xhr) {
                self.renderFailureMessages('ext_tables.php', xhr.responseJSON.brokenExtensions, $outputContainer);
              }).always(function() {
                self.unlockModal();
              })
            }).fail(function(xhr) {
              self.renderFailureMessages('ext_localconf.php', xhr.responseJSON.brokenExtensions, $outputContainer);
              $outputContainer.append(
                InfoBox.render(Severity.notice, 'Skipped scanning ext_tables.php files due to previous errors', '')
              );
              self.unlockModal();
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

    unlockModal: function() {
      this.currentModal.find(this.selectorOutputContainer).find('.alert-loading').remove();
      this.currentModal.find(this.selectorCheckTrigger).prop('disabled', false);
    },

    renderFailureMessages: function(scope, brokenExtensions, $outputContainer) {
      for (let i = 0; i < brokenExtensions.length; ++i) {
        let extension = brokenExtensions[i];

        let uninstallAction;
        if (!extension.isProtected) {
          uninstallAction = $('<button />', {'class': 'btn btn-danger t3js-extensionCompatTester-uninstall'})
            .attr('data-extension', extension.name)
            .text('Uninstall extension "' + extension.name + '"');
        }
        $outputContainer.append(
          InfoBox.render(
            Severity.error,
            'Loading ' + scope + ' of extension "' + extension.name + '" failed',
            (extension.isProtected ? 'Extension is mandatory and cannot be uninstalled.' : '')
          ),
          uninstallAction
        );
      }

      this.unlockModal();
    },

    loadExtLocalconf: function() {
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_localconf-token');
      return $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtLocalconf',
            'token': executeToken
          }
        }
      });
    },

    loadExtTables: function() {
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_tables-token');
      return $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        cache: false,
        data: {
          'install': {
            'action': 'extensionCompatTesterLoadExtTables',
            'token': executeToken
          }
        }
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
