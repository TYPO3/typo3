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
    'TYPO3/CMS/Backend/Icons'
  ],
  function($, Icons) {
    'use strict';

    TYPO3.Backend = {
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
        if (typeof Ext.getCmp('typo3-pagetree') !== 'undefined') {
          Ext.getCmp('typo3-pagetree').doLayout();
        }
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
        PageTree: {
          refreshTree: function() {
            if (typeof Ext.getCmp('typo3-pagetree') !== 'undefined') {
              Ext.getCmp('typo3-pagetree').refreshTree();
            }
          }
        },
        toggle: function() {
          $('.t3js-scaffold').toggleClass('scaffold-content-navigation-expanded')
        },
        cleanup: function() {
          $('.t3js-scaffold-modulemenu').removeAttr('style');
          $('.t3js-scaffold-content').removeAttr('style');
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
          $('.t3js-scaffold-content-navigation [data-component=' + component + ']').show();
        },
        setUrl: function(urlToLoad) {
          $('.t3js-scaffold').addClass('scaffold-content-navigation-expanded');
          $('.t3js-scaffold-content-navigation-iframe').attr('src', urlToLoad);
        },
        getUrl: function() {
          return $('.t3js-scaffold-content-navigation-iframe').attr('src');
        },
        refresh: function() {
          $('.t3js-scaffold-content-navigation-iframe')[0].contentWindow.location.reload();
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
        }
      },
      /**
       * Contentcontainer
       */
      ContentContainer: {
        // @deprecated since TYPO3 v8, will be removed in v9.
        // Use top.TYPO3.Backend.ContentContainer.get() instead of top.TYPO3.Backend.ContentContainer.iframe
        'iframe': $('.t3js-scaffold-content-module-iframe')[0].contentWindow,
        get: function() {
          return $('.t3js-scaffold-content-module-iframe')[0].contentWindow;
        },
        setUrl: function(urlToLoad) {
          TYPO3.Backend.Loader.start();
          return $('.t3js-scaffold-content-module-iframe')
            .attr('src', urlToLoad)
            .one('load', function() {
              TYPO3.Backend.Loader.finish();
            });
        },
        getUrl: function() {
          return $('.t3js-scaffold-content-module-iframe').attr('src');
        },
        refresh: function() {
          $('.t3js-scaffold-content-module-iframe')[0].contentWindow.location.reload();
        },
        getIdFromUrl: function() {
          if (this.getUrl) {
            return TYPO3.Utility.getParameterFromUrl(this.getUrl, 'id');
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
