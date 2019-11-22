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

import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');

interface Payload {
  url: string;
  method?: string;
  data?: { [key: string]: any},
  onfulfilled: Function;
  onrejected: Function;
  finally?: Function;
}

/**
 * Module: TYPO3/CMS/Install/Module/AjaxQueue
 */
class AjaxQueue {
  private requestCount: number = 0;
  private threshold: number = 10;
  private queue: Array<Payload> = [];

  public async add(payload: Payload): Promise<any> {
    const oldFinally = payload.finally;
    if (this.queue.length > 0 && this.requestCount <= this.threshold) {
      this.sendRequest(this.queue.shift()).finally((): void => {
        this.decrementRequestCount();
      });
    } else {
      this.decrementRequestCount();
    }

    if (oldFinally) {
      oldFinally(...arguments);
    }

    if (this.requestCount >= this.threshold) {
      this.queue.push(payload);
    } else {
      this.incrementRequestCount();
      this.sendRequest(payload);
    }
  }

  private async sendRequest(payload: Payload): Promise<any> {
    const request = new AjaxRequest(payload.url);
    let response: any;
    if (typeof payload.method !== 'undefined' && payload.method.toUpperCase() === 'POST') {
      response = request.post(payload.data);
    } else {
      response = request.withQueryArguments(payload.data || {}).get();
    }

    return response.then(payload.onfulfilled, payload.onrejected);
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
