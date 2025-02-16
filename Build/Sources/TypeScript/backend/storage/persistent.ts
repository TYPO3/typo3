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

export type UC = { [key: string]: string | number | boolean | null | UC };

/**
 * Module: @typo3/backend/storage/persistent
 * Wrapper for persistent storage in UC
 * @exports @typo3/backend/storage/persistent
 */
class Persistent {
  private data: UC|null = null;

  /**
   * Persistent storage, stores everything on the server via AJAX, does a greedy load on read
   * common functions get/set/clear
   *
   * @param {String} key
   * @returns {any}
   */
  public get(key: string): any {
    if (this.data === null) {
      this.data = this.loadFromServer();
    }

    return this.getRecursiveDataByDeepKey(this.data, key.split('.'));
  }

  /**
   * Store data persistent on server
   *
   * @param {String} key
   * @param {String} value
   * @returns {Promise<UC>}
   */
  public set(key: string, value: string|UC): Promise<UC> {
    if (this.data !== null) {
      this.data = this.setRecursiveDataByDeepKey(this.data, key.split('.'), value);
    }
    return this.storeOnServer(key, value);
  }

  /**
   * @param {string} key
   * @param {string} value
   * @returns {Promise<UC>}
   */
  public async addToList(key: string, value: string): Promise<UC> {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({
      action: 'addToList',
      key,
      value,
    });
    return this.resolveResponse(response);
  }

  /**
   * @param {string} key
   * @param {string} value
   * @returns {Promise<UC>}
   */
  public async removeFromList(key: string, value: string): Promise<UC> {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({
      action: 'removeFromList',
      key,
      value,
    });
    return this.resolveResponse(response);
  }

  public async unset(key: string): Promise<UC> {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({
      action: 'unset',
      key,
    });
    return this.resolveResponse(response);
  }

  /**
   * Clears the UC
   */
  public clear(): void {
    new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({
      action: 'clear',
    });
    this.data = null;
  }

  /**
   * Checks if a key was set before, useful to not do all the undefined checks all the time
   *
   * @param {string} key
   * @returns {boolean}
   */
  public isset(key: string): boolean {
    const value = this.get(key);
    return (typeof value !== 'undefined' && value !== null);
  }

  /**
   * Loads the data from outside, only used for the initial call from BackendController
   *
   * @param {UC} data
   */
  public load(data: UC): void {
    this.data = data;
  }

  /**
   * Loads all data from the server
   */
  private loadFromServer(): UC {
    const url = new URL(location.origin + TYPO3.settings.ajaxUrls.usersettings_process);
    url.searchParams.set('action', 'getAll');

    const request = new XMLHttpRequest();
    const async = false;
    request.open('GET', url.toString(), async);
    request.send();

    if (request.status === 200) {
      return JSON.parse(request.responseText);
    }

    throw `Unexpected response code ${request.status}, reason: ${request.responseText}`;
  }

  /**
   * Stores data on the server, and gets the updated data on return
   * to always be up-to-date inside the browser
   *
   * @param {string} key
   * @param {string|object} value
   * @returns {Promise<UC>}
   */
  private async storeOnServer(key: string, value: string|object): Promise<UC> {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({
      action: 'set',
      key,
      value,
    });
    return this.resolveResponse(response);
  }

  /**
   * Helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar] is called recursively by itself
   *
   * @param {object} data the data to be used as base
   * @param {string[]} keyParts the keyParts for the subtree
   * @returns {UC}
   */
  private getRecursiveDataByDeepKey(data: any, keyParts: string[]): any {
    if (keyParts.length === 1) {
      return (data || {})[keyParts[0]];
    }

    const firstKey = keyParts.shift();
    return this.getRecursiveDataByDeepKey(data[firstKey] || {}, keyParts);
  }

  /**
   * helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar]
   * is called recursively by itself
   *
   * @param {UC} data
   * @param {string[]} keyParts
   * @param {UC} value
   * @returns {UC}
   */
  private setRecursiveDataByDeepKey(data: UC, keyParts: string[], value: string | UC): UC {
    if (keyParts.length === 1) {
      data = data || {};
      data[keyParts[0]] = value;
    } else {
      const firstKey = keyParts.shift();
      data[firstKey] = this.setRecursiveDataByDeepKey((data[firstKey] as UC) || {}, keyParts, value);
    }
    return data;
  }

  private async resolveResponse(response: AjaxResponse): Promise<UC> {
    const resolvedResponse = await response.resolve();
    this.data = resolvedResponse;

    return resolvedResponse;
  }
}

export default new Persistent();
