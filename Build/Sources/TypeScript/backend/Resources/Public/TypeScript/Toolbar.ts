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

import * as $ from 'jquery';
import ModuleMenu = require('./ModuleMenu');

/**
 * Module: TYPO3/CMS/Backend/Toolbar
 * Toolbar component of the TYPO3 backend
 * @exports TYPO3/CMS/Backend/Toolbar
 */
class Toolbar {
  public static initialize(): void {
    Toolbar.initializeEvents();
  }

  private static initializeEvents(): void {
    $('.t3js-toolbar-item').parent().on('hidden.bs.dropdown', (): void => {
      $('.scaffold')
        .removeClass('scaffold-toolbar-expanded')
        .removeClass('scaffold-search-expanded');
    });
    $(document).on('click', '.toolbar-item [data-modulename]', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const moduleName = $(evt.target).closest('[data-modulename]').data('modulename');
      ModuleMenu.App.showModule(moduleName);
    });
    $(document).on('click', '.t3js-topbar-button-toolbar', (): void => {
      $('.scaffold')
        .removeClass('scaffold-modulemenu-expanded')
        .removeClass('scaffold-search-expanded')
        .toggleClass('scaffold-toolbar-expanded');
    });
    $(document).on('click', '.t3js-topbar-button-search', (): void => {
      $('.scaffold')
        .removeClass('scaffold-modulemenu-expanded')
        .removeClass('scaffold-toolbar-expanded')
        .toggleClass('scaffold-search-expanded');
    });
  }
}

$((): void => {
  Toolbar.initialize();
});
