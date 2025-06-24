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

import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/backend/key-bindings
 * @exports @typo3/backend/key-bindings
 */
class KeyBindings {

  constructor() {
    this.preventEscapePropagationInSearchInputs();
  }

  /**
   * Prevent Bootstrap modals or other global escape handlers from reacting
   * when an Escape keypress is triggered inside a type="search" input.
   * This ensures only the input clears (as expected) without closing modals.
   *
   * @todo Might be removed once we switched to native elements like <dialog> for modals
   */
  private preventEscapePropagationInSearchInputs(): void {
    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Escape' && e.target instanceof HTMLInputElement && e.target.type === 'search' && e.target.value !== '') {
        e.stopPropagation();
      }
    }, true).bindTo(document);
  }
}

export default new KeyBindings();
