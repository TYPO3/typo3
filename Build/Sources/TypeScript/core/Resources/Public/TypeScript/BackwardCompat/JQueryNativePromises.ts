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

import {ResponseError} from '../Ajax/ResponseError';

/**
 * Introduces a polyfill to support jQuery callbacks in native promises.
 */
/*! Based on https://www.promisejs.org/polyfills/promise-done-7.0.4.js */
export default class JQueryNativePromises {
  public static support(): void {
    if (typeof Promise.prototype.done !== 'function') {
      Promise.prototype.done = function (onFulfilled: Function): Promise<any> {
        return arguments.length ? this.then.apply(this, arguments) : Promise.prototype.then;
      };
    }

    if (typeof Promise.prototype.fail !== 'function') {
      Promise.prototype.fail = function (onRejected: Function): Promise<any> {
        this.catch(async (err: ResponseError): Promise<void> => {
          const response = err.response;
          onRejected(await JQueryNativePromises.createFakeXhrObject(response), 'error', response.statusText);
        });

        return this;
      };
    }
  }

  private static async createFakeXhrObject(response: Response): Promise<any> {
    const xhr: { [key: string ]: any } = {};
    xhr.readyState = 4;
    xhr.responseText = await response.text();
    xhr.responseURL = response.url;
    xhr.status = response.status;
    xhr.statusText = response.statusText;

    if (response.headers.has('Content-Type') && response.headers.get('Content-Type').includes('application/json')) {
      xhr.responseType = 'json';
      xhr.contentJSON = await response.json();
    } else {
      xhr.responseType = 'text';
    }

    return xhr;
  }
}
