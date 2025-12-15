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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import {
  ScaffoldState,
  SearchToggleRequestEvent,
  ToolbarToggleRequestEvent,
} from './viewport/scaffold-state';

/**
 * Module: @typo3/backend/toolbar
 * Toolbar component of the TYPO3 backend
 * @exports @typo3/backend/toolbar
 */
class Toolbar {
  public static initialize(): void {
    ScaffoldState.initialize();
    Toolbar.initializeEvents();
  }

  private static initializeEvents(): void {
    new RegularEvent('click', (): void => {
      document.dispatchEvent(new ToolbarToggleRequestEvent());
    }).bindTo(document.querySelector('.t3js-topbar-button-toolbar'));

    new RegularEvent('click', (): void => {
      document.dispatchEvent(new SearchToggleRequestEvent());
    }).bindTo(document.querySelector('.t3js-topbar-button-search'));
  }
}

DocumentService.ready().then(Toolbar.initialize);
