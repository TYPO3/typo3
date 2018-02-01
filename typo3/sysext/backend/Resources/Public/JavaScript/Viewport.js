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
 * Module: TYPO3/CMS/Backend/Viewport
 * Handles the main logic of the TYPO3 backend viewport
 * @exports TYPO3/CMS/Backend/Viewport
 */
define(
  'TYPO3/CMS/Backend/Viewport',
  [
    'jquery',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/Utility',
    'TYPO3/CMS/Backend/Event/ConsumerScope',
    'TYPO3/CMS/Backend/Event/TriggerRequest'
  ],
  function($, Icons, Utility, ConsumerScope, TriggerRequest) {
    'use strict';

    function resolveIFrameElement() {
      var $iFrame = $('.t3js-scaffold-content-module-iframe:first');
      if ($iFrame.length === 0) {
        return null;
      }
      return $iFrame.get(0);
    }

    TYPO3.Backend = {
      /**
       * @type {ConsumerScope}
       */
      consumerScope: ConsumerScope,

      initialize: function() {
        TYPO3.Backend.doLayout();
        $(window).on('resize', TYPO3.Backend.doLayout);
      },
      /**
       * This function is triggered whenever a re-layouting of component is needed
       */
      doLayout: function() {
        TYPO3.Backend.NavigationContainer.cleanup();
        TYPO3.Backend.NavigationContainer.calculateScrollbar();
        $('.t3js-topbar-header').css('padding-right', $('.t3js-scaffold-toolbar').outerWidth());
      },
      Loader: {
        start: function() {
          require(['nprogress'], function(NProgress) {
            NProgress.configure({parent: '.t3js-scaffold-content-module', showSpinner: false});
            NProgress.start();
          });
        },
        finish: function() {
          require(['nprogress'], function(NProgress) {
            NProgress.done();
          });
        }
      },
      NavigationContainer: {
        instance: null,

        PageTree: {
          refreshTree: function() {
            if (TYPO3.Backend.NavigationContainer.instance !== null) {
              TYPO3.Backend.NavigationContainer.instance.refreshTree();
            }
          },
          setTemporaryMountPoint: function(pid) {
            if (TYPO3.Backend.NavigationContainer.instance !== null) {
              TYPO3.Backend.NavigationContainer.instance.setTemporaryMountPoint(pid);
            }
          },
          unsetTemporaryMountPoint: function() {
            if (TYPO3.Backend.NavigationContainer.instance !== null) {
              TYPO3.Backend.NavigationContainer.instance.unsetTemporaryMountPoint();
            }
          }
        },
        toggle: function() {
          $('.t3js-scaffold').toggleClass('scaffold-content-navigation-expanded')
        },
        cleanup: function() {
          $('.t3js-scaffold-modulemenu').removeAttr('style');
          $('t3js-scaffold-content').removeAttr('style');
        },
        hide: function() {
          $('.t3js-topbar-button-navigationcomponent').attr('disabled', true);
          Icons.getIcon('actions-pagetree', Icons.sizes.small, 'overlay-readonly', null, Icons.markupIdentifiers.inline).done(function(icon) {
            $('.t3js-topbar-button-navigationcomponent').html(icon);
          });
          $('.t3js-scaffold').removeClass('scaffold-content-navigation-expanded');
          $('.t3js-scaffold-content-module').removeAttr('style');
        },
        show: function(component) {
          $('.t3js-topbar-button-navigationcomponent').attr('disabled', false);
          Icons.getIcon('actions-pagetree', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).done(function(icon) {
            $('.t3js-topbar-button-navigationcomponent').html(icon);
          });
          if (component !== undefined) {
            $('.t3js-scaffold').addClass('scaffold-content-navigation-expanded');
          }
          $('.t3js-scaffold-content-navigation [data-component]').hide();
          $('.t3js-scaffold-content-navigation [data-component="' + component + '"]').show();
        },
        /**
         * @param {string} urlToLoad
         * @param {InteractionRequest} [interactionRequest]
         * @return {jQuery.Deferred}
         */
        setUrl: function(urlToLoad, interactionRequest) {
          var deferred = TYPO3.Backend.consumerScope.invoke(
            new TriggerRequest('typo3.setUrl', interactionRequest)
          );
          deferred.then(function() {
            $('.t3js-scaffold').addClass('scaffold-content-navigation-expanded');
            $('.t3js-scaffold-content-navigation-iframe').attr('src', urlToLoad);
          });
          return deferred;
        },
        getUrl: function() {
          return $('.t3js-scaffold-content-navigation-iframe').attr('src');
        },
        /**
         * @param {boolean} forceGet
         */
        refresh: function(forceGet) {
          $('.t3js-scaffold-content-navigation-iframe')[0].contentWindow.location.reload(forceGet);
        },
        calculateScrollbar: function() {
          TYPO3.Backend.NavigationContainer.cleanup();
          var $scaffold = $('.t3js-scaffold');
          var $moduleMenuContainer = $('.t3js-scaffold-modulemenu');
          var $contentContainer = $('.t3js-scaffold-content');
          var $moduleMenu = $('.t3js-modulemenu');
          $moduleMenuContainer.css('overflow', 'auto');
          var moduleMenuContainerWidth = $moduleMenuContainer.outerWidth();
          var moduleMenuWidth = $moduleMenu.outerWidth();
          $moduleMenuContainer.removeAttr('style').css('overflow', 'hidden');
          if ($scaffold.hasClass('scaffold-modulemenu-expanded') === false) {
            $moduleMenuContainer.width(moduleMenuContainerWidth + (moduleMenuContainerWidth - moduleMenuWidth));
            $contentContainer.css('left', moduleMenuContainerWidth + (moduleMenuContainerWidth - moduleMenuWidth))
          } else {
            $moduleMenuContainer.removeAttr('style');
            $contentContainer.removeAttr('style');
          }
          $moduleMenuContainer.css('overflow', 'auto');
        },

        /**
         * Public method used by Naviagtion components to register themselves.
         * See TYPO3/CMS/Backend/PageTree/PageTreeElement->initialize
         *
         * @param {Object} component
         */
        setComponentInstance: function(component) {
          TYPO3.Backend.NavigationContainer.instance = component;
        }
      },
      /**
       * Content container manages the right site of the viewport (showing module specific content)
       */
      ContentContainer: {
        get: function() {
          return $('.t3js-scaffold-content-module-iframe')[0].contentWindow;
        },
        /**
         * @param {InteractionRequest} [interactionRequest]
         * @return {jQuery.Deferred}
         */
        beforeSetUrl: function(interactionRequest) {
          return TYPO3.Backend.consumerScope.invoke(
            new TriggerRequest('typo3.beforeSetUrl', interactionRequest)
          );
        },
        /**
         * @param {String} urlToLoad
         * @param {InteractionRequest} [interactionRequest]
         * @return {jQuery.Deferred}
         */
        setUrl: function(urlToLoad, interactionRequest) {
          var deferred;
          var iFrame = resolveIFrameElement();
          // abort, if no IFRAME can be found
          if (iFrame === null) {
            deferred = $.Deferred();
            deferred.reject();
            return deferred;
          }
          deferred = TYPO3.Backend.consumerScope.invoke(
            new TriggerRequest('typo3.setUrl', interactionRequest)
          );
          deferred.then(function() {
            TYPO3.Backend.Loader.start();
            $('.t3js-scaffold-content-module-iframe')
              .attr('src', urlToLoad)
              .one('load', function() {
                TYPO3.Backend.Loader.finish();
              });
          });
          return deferred;
        },
        getUrl: function() {
          return $('.t3js-scaffold-content-module-iframe').attr('src');
        },
        /**
         * @param {boolean} forceGet
         * @param {InteractionRequest} interactionRequest
         * @return {jQuery.Deferred}
         */
        refresh: function(forceGet, interactionRequest) {
          var deferred;
          var iFrame = resolveIFrameElement();
          // abort, if no IFRAME can be found
          if (iFrame === null) {
            deferred = $.Deferred();
            deferred.reject();
            return deferred;
          }
          deferred = TYPO3.Backend.consumerScope.invoke(
            new TriggerRequest('typo3.refresh', interactionRequest)
          );
          deferred.then(function() {
            iFrame.contentWindow.location.reload(forceGet);
          });
          return deferred;
        },
        getIdFromUrl: function() {
          if (this.getUrl) {
            return Utility.getParameterFromUrl(this.getUrl, 'id');
          } else {
            return 0;
          }
        }
      },
      Topbar: {
        topbarSelector: '.t3js-scaffold-header',
        refresh: function() {
          $.ajax(TYPO3.settings.ajaxUrls['topbar']).done(function(data) {
            $(TYPO3.Backend.Topbar.topbarSelector).html(data.topbar);
            $(TYPO3.Backend.Topbar.topbarSelector).trigger('t3-topbar-update');
          });
        },
        Toolbar: {
          registerEvent: function(callback) {
            $(callback);
            $(TYPO3.Backend.Topbar.topbarSelector).on('t3-topbar-update', callback);
          }
        }
      }
    };

    // start the module menu app
    TYPO3.Backend.initialize();
    return TYPO3.Backend;
  }
);
