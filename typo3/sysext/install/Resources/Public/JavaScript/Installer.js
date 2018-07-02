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
 * Walk through the installation process of TYPO3
 */
require([
  'jquery',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/PasswordStrength'
], function($, InfoBox, Severity, ProgressBar, PasswordStrength) {
  'use strict';

  $(function() {
    Installer.initialize();
  });

  var Installer = {
    selectorBody: '.t3js-body',
    selectorModuleContent: '.t3js-module-content',
    selectorMainContent: '.t3js-installer-content',
    selectorProgressBar: '.t3js-installer-progress',
    selectorDatabaseConnectOutput: '.t3js-installer-databaseConnect-output',
    selectorDatabaseSelectOutput: '.t3js-installer-databaseSelect-output',
    selectorDatabaseDataOutput: '.t3js-installer-databaseData-output',
    selectorDefaultConfigurationOutput: '.t3js-installer-defaultConfiguration-output',

    initialize: function() {
      var self = this;

      $(document).on('click', '.t3js-installer-environmentFolders-retry', function(e) {
        e.preventDefault();
        self.showEnvironmentAndFolders();
      });
      $(document).on('click', '.t3js-installer-environmentFolders-execute', function(e) {
        e.preventDefault();
        self.executeEnvironmentAndFolders();
      });
      $(document).on('click', '.t3js-installer-databaseConnect-execute', function(e) {
        e.preventDefault();
        self.executeDatabaseConnect();
      });
      $(document).on('click', '.t3js-installer-databaseSelect-execute', function(e) {
        e.preventDefault();
        self.executeDatabaseSelect();
      });
      $(document).on('click', '.t3js-installer-databaseData-execute', function(e) {
        e.preventDefault();
        self.executeDatabaseData();
      });
      $(document).on('click', '.t3js-installer-defaultConfiguration-execute', function(e) {
        e.preventDefault();
        self.executeDefaultConfiguration();
      });

      $(document).on('keyup', '.t3-install-form-password-strength', function() {
        PasswordStrength.initialize('.t3-install-form-password-strength');
      });

      // Database connect db driver selection
      $(document).on('change', '#t3js-connect-database-driver', function() {
        var driver = $(this).val();
        $('.t3-install-driver-data').hide();
        $('.t3-install-driver-data input').attr('disabled', 'disabled');
        $('#' + driver + ' input').attr('disabled', false);
        $('#' + driver).show();
      });

      this.setProgress(0);
      this.getMainLayout();
    },

    getUrl: function(action) {
      var url = location.href;
      url = url.replace(location.search, '');
      if (action !== undefined) {
        url = url + '?install[action]=' + action;
      }
      return url;
    },

    setProgress: function(done) {
      var $progressBar = $(this.selectorProgressBar);
      var percent = 0;
      if (done !== 0) {
        percent = (done / 5) * 100;
        $progressBar.find('.progress-bar').empty().text(done + ' / 5 - ' + percent + '% Complete');
      }
      $progressBar
        .find('.progress-bar')
        .css('width', percent + '%')
        .attr('aria-valuenow', percent);
    },

    getMainLayout: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('mainLayout'),
        cache: false,
        success: function(data) {
          $(self.selectorBody).empty().append(data.html);
          self.checkInstallerAvailable();
        }
      })
    },

    checkInstallerAvailable: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('checkInstallerAvailable'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkEnvironmentAndFolders();
          } else {
            self.showInstallerNotAvailable();
          }
        }
      });
    },

    showInstallerNotAvailable: function() {
      var $outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('showInstallerNotAvailable'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().append(data.html);
          }
        }
      });
    },

    checkEnvironmentAndFolders: function() {
      var self = this;
      this.setProgress(1);
      $.ajax({
        url: this.getUrl('checkEnvironmentAndFolders'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkTrustedHostsPattern();
          } else {
            self.showEnvironmentAndFolders();
          }
        }
      });
    },

    showEnvironmentAndFolders: function() {
      var $outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('showEnvironmentAndFolders'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().html(data.html);
            var $detailContainer = $('.t3js-installer-environment-details');
            var hasMessage = false;
            if (Array.isArray(data.environmentStatusErrors)) {
              data.environmentStatusErrors.forEach(function(element) {
                hasMessage = true;
                var message = InfoBox.render(element.severity, element.title, element.message);
                $detailContainer.append(message);
              });
            }
            if (Array.isArray(data.environmentStatusWarnings)) {
              data.environmentStatusWarnings.forEach(function(element) {
                hasMessage = true;
                var message = InfoBox.render(element.severity, element.title, element.message);
                $detailContainer.append(message);
              });
            }
            if (Array.isArray(data.structureErrors)) {
              data.structureErrors.forEach(function(element) {
                hasMessage = true;
                var message = InfoBox.render(element.severity, element.title, element.message);
                $detailContainer.append(message);
              });
            }
            if (hasMessage === true) {
              $detailContainer.show();
              $('.t3js-installer-environmentFolders-bad').show();
            } else {
              $('.t3js-installer-environmentFolders-good').show();
            }
          }
        }
      });
    },

    executeEnvironmentAndFolders: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('executeEnvironmentAndFolders'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkTrustedHostsPattern();
          } else {
            // @todo message output handling
          }
        }
      });
    },

    checkTrustedHostsPattern: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('checkTrustedHostsPattern'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.executeSilentConfigurationUpdate();
          } else {
            self.executeAdjustTrustedHostsPattern();
          }
        }
      });
    },

    executeAdjustTrustedHostsPattern: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('executeAdjustTrustedHostsPattern'),
        cache: false,
        success: function(data) {
          self.executeSilentConfigurationUpdate();
        }
      });
    },

    executeSilentConfigurationUpdate: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('executeSilentConfigurationUpdate'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkDatabaseConnect();
          } else {
            self.executeSilentConfigurationUpdate();
          }
        }
      });
    },

    checkDatabaseConnect: function() {
      this.setProgress(2);
      var self = this;
      $.ajax({
        url: this.getUrl('checkDatabaseConnect'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkDatabaseSelect();
          } else {
            self.showDatabaseConnect();
          }
        }
      });
    },

    showDatabaseConnect: function() {
      var $outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('showDatabaseConnect'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().html(data.html);
            $('#t3js-connect-database-driver').trigger('change');
          }
        }
      });
    },

    executeDatabaseConnect: function() {
      var self = this;
      var $outputContainer = $(this.selectorDatabaseConnectOutput);
      var postData = {
        'install[action]': 'executeDatabaseConnect',
        'install[token]': $(self.selectorModuleContent).data('installer-database-connect-execute-token')
      };
      $($(this.selectorBody + ' form').serializeArray()).each(function() {
        postData[this.name] = this.value;
      });
      $.ajax({
        url: this.getUrl(),
        cache: false,
        method: 'POST',
        data: postData,
        success: function(data) {
          if (data.success === true) {
            self.checkDatabaseSelect();
          } else {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.empty().append(message);
              });
            }
          }
        }
      });
    },

    checkDatabaseSelect: function() {
      var self = this;
      this.setProgress(3);
      $.ajax({
        url: this.getUrl('checkDatabaseSelect'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkDatabaseData();
          } else {
            self.showDatabaseSelect();
          }
        }
      });
    },

    showDatabaseSelect: function() {
      var $outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('showDatabaseSelect'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().html(data.html);
          }
        }
      });
    },

    executeDatabaseSelect: function() {
      var self = this;
      var $outputContainer = $(this.selectorDatabaseSelectOutput);
      var postData = {
        'install[action]': 'executeDatabaseSelect',
        'install[token]': $(self.selectorModuleContent).data('installer-database-select-execute-token')
      };
      $($(this.selectorBody + ' form').serializeArray()).each(function() {
        postData[this.name] = this.value;
      });
      $.ajax({
        url: this.getUrl(),
        cache: false,
        method: 'POST',
        data: postData,
        success: function(data) {
          if (data.success === true) {
            self.checkDatabaseData();
          } else {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.empty().append(message);
              });
            }
          }
        }
      });
    },

    checkDatabaseData: function() {
      var self = this;
      this.setProgress(4);
      $.ajax({
        url: this.getUrl('checkDatabaseData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.showDefaultConfiguration();
          } else {
            self.showDatabaseData();
          }
        }
      });
    },

    showDatabaseData: function() {
      var $outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('showDatabaseData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().html(data.html);
          }
        }
      });
    },

    executeDatabaseData: function() {
      var self = this;
      var $outputContainer = $(this.selectorDatabaseDataOutput);
      var postData = {
        'install[action]': 'executeDatabaseData',
        'install[token]': $(self.selectorModuleContent).data('installer-database-data-execute-token')
      };
      $($(this.selectorBody + ' form').serializeArray()).each(function() {
        postData[this.name] = this.value;
      });
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().html(message);
      $.ajax({
        url: this.getUrl(),
        cache: false,
        method: 'POST',
        data: postData,
        success: function(data) {
          if (data.success === true) {
            self.showDefaultConfiguration();
          } else {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                var message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.empty().append(message);
              });
            }
          }
        }
      });
    },

    showDefaultConfiguration: function() {
      var $outputContainer = $(this.selectorMainContent);
      this.setProgress(5);
      $.ajax({
        url: this.getUrl('showDefaultConfiguration'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $outputContainer.empty().html(data.html);
          }
        }
      });
    },

    executeDefaultConfiguration: function() {
      var self = this;
      var postData = {
        'install[action]': 'executeDefaultConfiguration',
        'install[token]': $(self.selectorModuleContent).data('installer-default-configuration-execute-token')
      };
      $($(this.selectorBody + ' form').serializeArray()).each(function() {
        postData[this.name] = this.value;
      });
      $.ajax({
        url: this.getUrl(),
        cache: false,
        method: 'POST',
        data: postData,
        success: function(data) {
          top.location.href = data.redirect;
        }
      });
    }
  };
});
