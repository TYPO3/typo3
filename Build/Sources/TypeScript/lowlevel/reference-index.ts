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

import NProgress from 'nprogress';
import RegularEvent from '@typo3/core/event/regular-event';

enum Selectors {
  actionsContainerSelector = '.t3js-reference-index-actions'
}

/**
 * Module: @typo3/lowlevel/reference-index
 * Show progress indicator and disable buttons
 */
class ReferenceIndex {

  constructor() {
    this.registerActionButtonEvents();
  }

  private registerActionButtonEvents(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      NProgress.configure({showSpinner: false});
      NProgress.start();
      // Disable all action buttons to avoid duplicate execution
      Array.from(target.parentNode.querySelectorAll('button')).forEach((button: HTMLButtonElement) => {
        button.classList.add('disabled');
      });
    }).delegateTo(<HTMLElement>document.querySelector(Selectors.actionsContainerSelector), 'button');
  }

}

export default new ReferenceIndex();
