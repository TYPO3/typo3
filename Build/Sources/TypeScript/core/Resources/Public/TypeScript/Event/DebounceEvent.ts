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

import {Listener} from './EventInterface';
import RegularEvent from './RegularEvent';

/**
 * Debounces an event listener that is executed after the event happened, either at the start or at the end.
 * A debounced event listener is not executed again until a certain amount of time has passed without it being called.
 */
class DebounceEvent extends RegularEvent {
  constructor(eventName: string, callback: Listener, wait: number = 250, immediate: boolean = false) {
    super(eventName, callback);
    this.callback = this.debounce(this.callback, wait, immediate);
  }

  private debounce(callback: Listener, wait: number, immediate: boolean): Listener {
    let timeout: number = null;

    return function (this: Node, ...args: any[]): void {
      const callNow = immediate && !timeout;

      // Reset timeout handler to make sure the callback is executed once
      clearTimeout(timeout);
      if (callNow) {
        callback.apply(this, args);
        timeout = setTimeout((): void => {
          timeout = null;
        }, wait);
      } else {
        timeout = setTimeout((): void => {
          timeout = null;
          if (!immediate) {
            callback.apply(this, args);
          }
        }, wait);
      }
    };
  }
}

export default DebounceEvent;
