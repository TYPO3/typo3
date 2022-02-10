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

import {Listener} from './event-interface';
import RegularEvent from './regular-event';

/**
 * Creates a event aimed for high performance visual operations
 */
class RequestAnimationFrameEvent extends RegularEvent {
  constructor(eventName: string, callback: Listener) {
    super(eventName, callback);
    this.callback = this.req(this.callback);
  }

  private req(callback: Listener): Listener {
    let timeout: number = null;

    return () => {
      const context: any = this;
      const args = arguments;

      if (timeout) {
        window.cancelAnimationFrame(timeout);
      }

      timeout = window.requestAnimationFrame(function () {
        // Run our scroll functions
        callback.apply(context, args);
      });
    };
  }
}

export default RequestAnimationFrameEvent;
