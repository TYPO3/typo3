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
 * Module: TYPO3/CMS/Backend/Toolbar
 * Toolbar component of the TYPO3 backend
 * @exports TYPO3/CMS/Backend/Toolbar
 */
require(
  [
    'jquery',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/ModuleMenu'
  ],
  function($) {

    TYPO3.Toolbar = {};
    TYPO3.Toolbar.App = {
      initialize: function() {
        this.initializeEvents();
      },
      initializeEvents: function() {
        $('.t3js-toolbar-item').parent().on('hidden.bs.dropdown', function() {
          $('.scaffold')
            .removeClass('scaffold-toolbar-expanded')
            .removeClass('scaffold-search-expanded');
        });
        $(document).on('click', '.toolbar-item [data-modulename]', function(evt) {
          var moduleName = $(this).data('modulename');
          TYPO3.ModuleMenu.App.showModule(moduleName);
          evt.preventDefault();
        });
        $(document).on('click', '.t3js-topbar-button-toolbar', function() {
          $('.scaffold')
            .removeClass('scaffold-modulemenu-expanded')
            .removeClass('scaffold-search-expanded')
            .toggleClass('scaffold-toolbar-expanded');
        });
        $(document).on('click', '.t3js-topbar-button-search', function() {
          $('.scaffold')
            .removeClass('scaffold-modulemenu-expanded')
            .removeClass('scaffold-toolbar-expanded')
            .toggleClass('scaffold-search-expanded');
        });
      }
    };
    // start the module menu app
    TYPO3.Toolbar.App.initialize();
  }
);
