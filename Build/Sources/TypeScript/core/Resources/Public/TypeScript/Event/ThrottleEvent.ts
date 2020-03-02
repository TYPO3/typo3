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
import RegularEvent = require('./RegularEvent');

/**
 * Throttles the event listener to be called only after a defined time during the event's execution over time.
 */
class ThrottleEvent extends RegularEvent {
  constructor(eventName: string, callback: Listener, limit: number) {
    super(eventName, callback);
    this.callback = this.throttle(callback, limit);
  }

  private throttle(callback: Listener, limit: number): Listener {
    let wait: boolean = false;

    return function (this: Node, ...args: any[]): void {
      if (wait) {
        return;
      }

      callback.apply(this, args);
      wait = true;

      setTimeout((): void => {
        wait = false;

        // Wait time is over, execute callback again to have final state
        callback.apply(this, args);
      }, limit);
    };
  }
}

export = ThrottleEvent;
