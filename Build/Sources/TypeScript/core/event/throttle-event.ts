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
 * Throttles the event listener to be called only after a defined time during the event's execution over time.
 */
class ThrottleEvent extends RegularEvent {
  constructor(eventName: string, callback: Listener, limit: number) {
    super(eventName, callback);
    this.callback = this.throttle(callback, limit);
  }

  private throttle(callback: Listener, limit: number): Listener {
    let eventData: unknown[] | null = null;
    let intervalId: number | null = null;

    return function (this: Node, ...args: unknown[]): void {
      eventData = args;
      if (intervalId !== null) {
        // Bail out, a previous call to dispatch invoked an interval
        // that will pick up eventData once the timeout occured.
        return;
      }
      const dispatch = () => {
        if (eventData === null) {
          // No event since the last immediate dispatch, start a new fresh interval-phase
          clearInterval(intervalId);
          intervalId = null;
          return;
        }
        callback.apply(this, eventData);
        eventData = null;
      };
      // immediate dispatch, no need to wait as no timer is active
      dispatch();
      intervalId = setInterval(dispatch, limit);
    };
  }
}

export default ThrottleEvent;
