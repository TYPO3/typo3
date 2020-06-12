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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

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
    new RegularEvent('click', (): void => {
      const scaffold = document.querySelector('.scaffold');
      scaffold.classList.remove('scaffold-modulemenu-expanded', 'scaffold-search-expanded');
      scaffold.classList.toggle('scaffold-toolbar-expanded');
    }).bindTo(document.querySelector('.t3js-topbar-button-toolbar'));

    new RegularEvent('click', (): void => {
      const scaffold = document.querySelector('.scaffold');
      scaffold.classList.remove('scaffold-modulemenu-expanded', 'scaffold-toolbar-expanded');
      scaffold.classList.toggle('scaffold-search-expanded');
    }).bindTo(document.querySelector('.t3js-topbar-button-search'))
  }
}

DocumentService.ready().then(Toolbar.initialize);
