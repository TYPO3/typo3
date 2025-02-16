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

import RegularEvent from './regular-event';
import type { Listener } from './event-interface';

/**
 * Debounces an event listener that is executed after the event happened.
 * A debounced event listener is not executed again until a certain amount of time has passed without it being called.
 */
class DebounceEvent extends RegularEvent {
  constructor(eventName: string, callback: Listener, wait: number = 250) {
    super(eventName, callback);
    this.callback = this.debounce(this.callback, wait);
  }

  private debounce(callback: Listener, wait: number): Listener {
    let timeout: number = null;

    return function (this: Node, ...args: unknown[]): void {
      clearTimeout(timeout);
      timeout = setTimeout((): void => {
        timeout = null;
        callback.apply(this, args);
      }, wait);
    };
  }
}

export default DebounceEvent;
