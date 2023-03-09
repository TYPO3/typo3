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
import PersistentStorage from './storage/persistent';
import '@typo3/backend/element/icon-element';

enum IdentifierEnum {
  hiddenElements = '.t3js-hidden-record',
}

/**
 * Module: @typo3/backend/page-actions
 * JavaScript implementations for page actions
 */
class PageActions {
  constructor() {
    DocumentService.ready().then((): void => {
      const showHiddenElementsCheckbox = document.getElementById('checkShowHidden') as HTMLInputElement;
      if (showHiddenElementsCheckbox !== null) {
        new RegularEvent('change', this.toggleContentElementVisibility).bindTo(showHiddenElementsCheckbox);
      }
    });
  }

  /**
   * Toggles the "Show hidden content elements" checkbox
   */
  private toggleContentElementVisibility(e: Event): void {
    const me = e.target as HTMLInputElement;
    const hiddenElements = document.querySelectorAll(IdentifierEnum.hiddenElements) as NodeListOf<HTMLElement>;

    // show a spinner to show activity
    const spinner = document.createElement('span');
    spinner.classList.add('form-check-spinner');
    spinner.append(document.createRange().createContextualFragment('<typo3-backend-icon identifier="spinner-circle" size="small"></typo3-backend-icon>'));

    me.hidden = true;
    me.insertAdjacentElement('afterend', spinner);

    for (const hiddenElement of hiddenElements) {
      hiddenElement.style.display = 'block';
      const scrollHeight = hiddenElement.scrollHeight;
      hiddenElement.style.display = '';

      if (!me.checked) {
        // We use requestAnimationFrame() as we have to set the container's height at first before resizing to 0px
        // results in a smooth animation.
        requestAnimationFrame(function() {
          hiddenElement.style.height = scrollHeight + 'px';
          requestAnimationFrame(function() {
            hiddenElement.style.height = 0 + 'px';
          });
        });
      } else {
        hiddenElement.style.height = scrollHeight + 'px';
      }
    }

    PersistentStorage.set('moduleData.web_layout.showHidden', me.checked ? '1' : '0').then((): void => {
      me.hidden = false;
      spinner.remove();
    });
  }
}

export default new PageActions();
