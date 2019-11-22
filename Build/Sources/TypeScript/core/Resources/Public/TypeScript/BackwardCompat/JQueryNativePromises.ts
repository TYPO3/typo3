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

/**
 * Introduces a polyfill to support jQuery callbacks in native promises. This approach has been adopted from
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
        const self = arguments.length ? this.catch.apply(this, arguments) : Promise.prototype.catch;
        self.catch(function (err: string) {
          setTimeout(function () {
            throw err
          }, 0)
        });

        return self;
      };
    }
  }
}
