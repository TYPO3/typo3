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

import * as $ from 'jquery';

/**
 * Module: TYPO3/CMS/Install/Module/AjaxQueue
 */
class AjaxQueue {
  private requestCount: number = 0;
  private threshold: number = 10;
  private queue: Array<any> = [];

  public add(payload: JQueryAjaxSettings): void {
    const oldComplete = payload.complete;
    payload.complete = (jqXHR: JQueryXHR, textStatus: string): void => {
      if (this.queue.length > 0 && this.requestCount <= this.threshold) {
        $.ajax(this.queue.shift()).always((): void => {
          this.decrementRequestCount();
        });
      } else {
        this.decrementRequestCount();
      }

      if (oldComplete) {
        oldComplete(jqXHR, textStatus);
      }
    };

    if (this.requestCount >= this.threshold) {
      this.queue.push(payload);
    } else {
      this.incrementRequestCount();
      $.ajax(payload);
    }
  }

  private incrementRequestCount(): void {
    this.requestCount++;
  }

  private decrementRequestCount(): void {
    if (this.requestCount > 0) {
      this.requestCount--;
    }
  }
}

export = new AjaxQueue();
