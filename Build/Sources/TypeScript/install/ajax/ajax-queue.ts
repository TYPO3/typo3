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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

interface Payload {
  url: string;
  method?: string;
  data?: { [key: string]: any},
  onfulfilled: (value: AjaxResponse) => Promise<void>;
  onrejected: (reason: string) => void;
  finally?: () => void;
}

/**
 * Module: @typo3/install/module/ajax-queue
 */
class AjaxQueue {
  private requests: Array<AjaxRequest> = [];
  private requestCount: number = 0;
  private readonly threshold: number = 5;
  private queue: Array<Payload> = [];

  public add(payload: Payload): void {
    this.queue.push(payload);
    this.handleNext();
  }

  public flush(): void {
    this.queue = [];
    this.requests.forEach((request: AjaxRequest) => request.abort());
    this.requests = [];
  }

  private handleNext(): void {
    if (this.queue.length > 0 && this.requestCount < this.threshold) {
      this.incrementRequestCount();
      this.sendRequest(this.queue.shift()).finally((): void => {
        this.decrementRequestCount();
        this.handleNext();
      });
    }
  }

  private async sendRequest(payload: Payload): Promise<void> {
    const request = new AjaxRequest(payload.url);
    let response: Promise<AjaxResponse>;
    if (typeof payload.method !== 'undefined' && payload.method.toUpperCase() === 'POST') {
      response = request.post(payload.data);
    } else {
      response = request.withQueryArguments(payload.data || {}).get();
    }

    this.requests.push(request);
    return response.then(payload.onfulfilled, payload.onrejected).then((): void => {
      const idx = this.requests.indexOf(request);
      this.requests.splice(idx, 1);
    });
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

export default new AjaxQueue();
