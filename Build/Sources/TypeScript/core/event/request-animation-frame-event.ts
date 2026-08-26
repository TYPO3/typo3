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
 * Creates a event aimed for high performance visual operations
 */
class RequestAnimationFrameEvent<E extends Event = Event, T extends Element = Element> extends RegularEvent<E, T> {
  constructor(eventName: string, callback: Listener<E, T>) {
    super(eventName, callback);
    this.callback = this.req(this.callback);
  }

  private req(callback: Listener<E, T>): Listener<E, T> {
    let timeout: number = null;

    return (...args: unknown[]) => {
      if (timeout) {
        window.cancelAnimationFrame(timeout);
      }

      timeout = window.requestAnimationFrame(() => {
        // Run our scroll functions
        callback.apply(this, args as Parameters<Listener<E, T>>);
      });
    };
  }
}

export default RequestAnimationFrameEvent;
