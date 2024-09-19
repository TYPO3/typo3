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
      const pageLayoutToggleShowHidden = document.getElementById('pageLayoutToggleShowHidden') as HTMLButtonElement|null;
      if (pageLayoutToggleShowHidden !== null) {
        new RegularEvent('click', this.toggleContentElementVisibility).bindTo(pageLayoutToggleShowHidden);
      }
    });
  }

  /**
   * Toggles the "Show hidden content elements"
   */
  private toggleContentElementVisibility(e: Event): void {
    const me = e.target as HTMLButtonElement;
    const hiddenElements = document.querySelectorAll(IdentifierEnum.hiddenElements) as NodeListOf<HTMLElement>;
    const show = me.dataset.dropdowntoggleStatus !== 'active';
    me.disabled = true;

    for (const hiddenElement of hiddenElements) {
      hiddenElement.style.display = 'flow-root';
      const scrollHeight = hiddenElement.scrollHeight;

      // Always set `overflow: clip` after storing scrollHeight
      // * For hidden state `height: 0px` is already set.
      // * For visible state setting `overflow: clip` is fine anyway.
      hiddenElement.style.overflow = 'clip';

      if (!show) {
        // * Invisible elements must not be accessible/focusable by keyboard.
        // * Spacing between content elements is kept uniform by collapsed margins,
        //   hidden elements have a height of 0 and the margins of the surrounding elements
        //   cannot collapse, causing a visual gap.
        // Therefore do not display the element at all by setting `display: none`.
        hiddenElement.addEventListener('transitionend', (): void => {
          hiddenElement.style.display = 'none';
          hiddenElement.style.overflow = '';
        }, { once: true });

        // We use requestAnimationFrame() as we have to set the container's height at first before resizing to
        // collapsed-element-height. This results in a smooth animation.
        requestAnimationFrame(function() {
          hiddenElement.style.height = scrollHeight + 'px';
          requestAnimationFrame(function() {
            hiddenElement.style.height = 0 + 'px';
          });
        });
      } else {
        hiddenElement.addEventListener('transitionend', (): void => {
          hiddenElement.style.display = '';
          hiddenElement.style.overflow = '';
          hiddenElement.style.height = '';
        }, { once: true });

        hiddenElement.style.height = scrollHeight + 'px';
      }
    }

    me.dataset.dropdowntoggleStatus = show ? 'active' : 'inactive';
    PersistentStorage.set('moduleData.web_layout.showHidden', show ? '1' : '0').then((): void => {
      me.disabled = false;
    });
  }
}

export default new PageActions();
