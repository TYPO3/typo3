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
 * Module: TYPO3/CMS/Install/Router
 */
define([
  'jquery',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Icons'
], function($, InfoBox, Severity, ProgressBar, Modal, Icons) {
  'use strict';

  return {
    selectorBody: '.t3js-body',
    selectorMainContent: '.t3js-module-body',

    initialize: function() {
      var self = this;

      this.registerInstallToolRoutes();

      $(document).on('click', '.t3js-login-lockInstallTool', function(e) {
        e.preventDefault();
        self.logout();
      });
      $(document).on('click', '.t3js-login-login', function(e) {
        e.preventDefault();
        self.login();
      });
      $(document).on('keydown', '#t3-install-form-password', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          $('.t3js-login-login').click();
        }
      });

      $(document).on('click', '.card .btn', function(e) {
        e.preventDefault();

        var $me = $(e.currentTarget);
        var requireModule = $me.data('require');
        var inlineState = $me.data('inline');
        var isInline = typeof inlineState !== 'undefined' && parseInt(inlineState) === 1;
        if (isInline) {
          require([requireModule], function(aModule) {
            if (typeof aModule.initialize !== 'undefined') {
              aModule.initialize($me);
            }
          });
        } else {
          var modalTitle = $me.closest('.card').find('.card-title').html();
          var modalSize = $me.data('modalSize') || Modal.sizes.large;
          var $modal = Modal.advanced({
            type: Modal.types.default,
            title: modalTitle,
            size: modalSize,
            content: $('<div class="modal-loading">'),
            additionalCssClasses: ['install-tool-modal'],
            callback: function (currentModal) {
              require([requireModule], function (aModule) {
                if (typeof aModule.initialize !== 'undefined') {
                  aModule.initialize(currentModal);
                }
              });
            }
          });
          Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done(function(icon) {
            $modal.find('.modal-loading').append(icon);
          });
        }
      });

      if ($(this.selectorBody).data('context') === 'backend') {
        this.executeSilentConfigurationUpdate();
      } else {
        this.preAccessCheck();
      }
    },

    registerInstallToolRoutes: function() {
      if (typeof TYPO3.settings === 'undefined') {
        TYPO3.settings = {
          ajaxUrls: {
            icons: '?install[controller]=icon&install[action]=getIcon',
            icons_cache: '?install[controller]=icon&install[action]=getCacheIdentifier'
          }
        }
      }
    },

    getUrl: function(action, controller) {
      var url = location.href;
      var context = $(this.selectorBody).data('context');
      url = url.replace(location.search, '');
      if (controller === undefined) {
        controller = $(this.selectorBody).data('controller');
      }
      url = url + '?install[controller]=' + controller;
      if (context !== undefined && context !== '') {
        url = url + '&install[context]=' + context;
      }
      if (action !== undefined) {
        url = url + '&install[action]=' + action;
      }
      return url;
    },

    preAccessCheck: function() {
      this.updateLoadingInfo("Execute pre access check");
      var self = this;
      $.ajax({
        url: this.getUrl("preAccessCheck", "layout"),
        cache: false,
        success: function(data) {
          if (data.installToolLocked) {
            self.checkEnableInstallToolFile();
          } else if (!data.isAuthorized) {
            self.showLogin();
          } else {
            self.executeSilentConfigurationUpdate();
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      })
    },

    executeSilentConfigurationUpdate: function() {
      var self = this;
      this.updateLoadingInfo('Checking session and executing silent configuration update');
      $.ajax({
        url: this.getUrl('executeSilentConfigurationUpdate', 'layout'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.executeSilentLegacyExtConfExtensionConfigurationUpdate();
          } else {
            self.executeSilentConfigurationUpdate();
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    /**
     * Legacy layer to upmerge LocalConfiguration EXT/extConf serialized array keys
     * to EXTENSIONS array in LocalConfiguration for initial update from v8 to v9.
     *
     * @deprecated since TYPO3 v9, will be removed with v10 - re-route executeSilentConfigurationUpdate()
     * to executeSilentExtensionConfigurationUpdate() on removal of this function.
     */
    executeSilentLegacyExtConfExtensionConfigurationUpdate: function() {
      var self = this;
      this.updateLoadingInfo('Executing silent extension configuration update');
      $.ajax({
        url: this.getUrl('executeSilentLegacyExtConfExtensionConfigurationUpdate', 'layout'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.executeSilentExtensionConfigurationSynchronization();
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    /**
     * Extensions which come with new default settings in ext_conf_template.txt extension
     * configuration files get their new defaults written to LocalConfiguration.
     */
    executeSilentExtensionConfigurationSynchronization: function() {
      var self = this;
      this.updateLoadingInfo('Executing silent extension configuration synchronization');
      $.ajax({
        url: this.getUrl('executeSilentExtensionConfigurationSynchronization', 'layout'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.loadMainLayout();
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    loadMainLayout: function() {
      var self = this;
      var $outputContainer = $(this.selectorBody);
      this.updateLoadingInfo('Loading main layout');
      $.ajax({
        url: this.getUrl('mainLayout', 'layout'),
        cache: false,
        success: function(data) {
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            $outputContainer.empty().append(data.html);
            // Mark main module as active in standalone
            if ($(self.selectorBody).data('context') !== 'backend') {
              var controller = $outputContainer.data('controller');
              $outputContainer.find('.t3js-mainmodule[data-controller="' + controller + '"]').addClass('active');
            }
            self.loadCards();
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    handleAjaxError: function(xhr, $outputContainer) {
      var message = '';
      if (xhr.status === 403) {
        // Install tool session expired - depending on context render error message or login
        var context = $(this.selectorBody).data('context');
        if (context === 'backend') {
          message = InfoBox.render(
            Severity.error,
            'The install tool session expired. Please reload the backend and try again.'
          );
          $(this.selectorBody).empty().append(message);
        } else {
          this.checkEnableInstallToolFile();
        }
      } else {
        // @todo Recovery tests should be started here
        var url = this.getUrl(undefined, 'upgrade');
        message =
          '<div class="t3js-infobox callout callout-sm callout-danger">'
            + '<div class="callout-body">'
              + '<p>Something went wrong. Please use <b><a href="' + url + '">Check for broken'
              + ' extensions</a></b> to see if a loaded extension breaks this part of the install tool'
              + ' and unload it.</p>'
              + '<p>The box below may additionally reveal further details on what went wrong depending on your debug settings.'
              + ' It may help to temporarily switch to debug mode using <b>Settings > Configuration Presets > Debug settings.</b></p>'
              + '<p>If this error happens at an early state and no full exception back trace is shown, it may also help'
              + ' to manually increase debugging output in <code>typo3conf/LocalConfiguration.php</code>:'
              + '<code>[\'BE\'][\'debug\'] => true</code>, <code>[\'SYS\'][\'devIPmask\'] => \'*\'</code>, <code>[\'SYS\'][\'displayErrors\'] => 1</code>,'
              + '<code>[\'SYS\'][\'systemLogLevel\'] => 0</code>, <code>[\'SYS\'][\'exceptionalErrors\'] => 12290</code></p>'
            + '</div>'
          + '</div>'
          + '<div class="panel-group" role="tablist" aria-multiselectable="true">'
            + '<div class="panel panel-default panel-flat searchhit">'
              + '<div class="panel-heading" role="tab" id="heading-error">'
                + '<h3 class="panel-title">'
                  + '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-error" aria-expanded="true" aria-controls="collapse-error" class="collapsed">'
                    + '<span class="caret"></span>'
                    + '<strong>Ajax error</strong>'
                  + '</a>'
                +'</h3>'
              + '</div>'
              + '<div id="collapse-error" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-error">'
              + '<div class="panel-body">'
                + xhr.responseText
              + '</div>'
            + '</div>'
          + '</div>';

        if (typeof $outputContainer !== 'undefined') {
          // Write to given output container. This is typically a modal if given
          $outputContainer.empty().html(message);
        } else {
          // Else write to main frame
          $(this.selectorBody).empty().html(message);
        }
      }
    },

    checkEnableInstallToolFile: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('checkEnableInstallToolFile'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.checkLogin();
          } else {
            self.showEnableInstallTool();
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    showEnableInstallTool: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('showEnableInstallToolFile'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $(self.selectorBody).empty().append(data.html);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    checkLogin: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('checkLogin'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.loadMainLayout();
          } else {
            self.showLogin();
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    showLogin: function() {
      var self = this;
      $.ajax({
        url: this.getUrl('showLogin'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            $(self.selectorBody).empty().append(data.html);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    login: function() {
      var self = this;
      var $outputContainer = $('.t3js-login-output');
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().html(message);
      $.ajax({
        url: this.getUrl(),
        cache: false,
        method: 'POST',
        data: {
          'install': {
            'action': 'login',
            'token': $('[data-login-token]').data('login-token'),
            'password': $('.t3-install-form-input-text').val()
          }
        },
        success: function(data) {
          if (data.success === true) {
            self.executeSilentConfigurationUpdate();
          } else {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().html(message);
            });
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    logout: function() {
      var self = this;
      $.ajax({
        url: self.getUrl('logout'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.showEnableInstallTool();
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    loadCards: function() {
      var self = this;
      var outputContainer = $(this.selectorMainContent);
      $.ajax({
        url: this.getUrl('cards'),
        cache: false,
        success: function(data) {
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            outputContainer.empty().append(data.html);
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            outputContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          self.handleAjaxError(xhr);
        }
      });
    },

    updateLoadingInfo: function(info) {
      var $outputContainer = $(this.selectorBody);
      $outputContainer.find('#t3js-ui-block-detail').text(info);
    }
  };
});
