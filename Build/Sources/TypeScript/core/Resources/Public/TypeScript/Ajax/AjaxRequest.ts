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

import JQueryNativePromises from '../BackwardCompat/JQueryNativePromises';
import {AjaxResponse} from './AjaxResponse';
import {ResponseError} from './ResponseError';

type GenericKeyValue = { [key: string]: any};

class AjaxRequest {
  private static defaultOptions: RequestInit = {
    credentials: 'same-origin'
  };

  private readonly url: string;
  private readonly abortController: AbortController;
  private queryArguments: string = '';

  /**
   * Transforms the incoming object to a flat FormData object used for POST and PUT
   *
   * @param {GenericKeyValue} data
   * @return {FormData}
   */
  private static transformToFormData(data: GenericKeyValue): FormData {
    const flattenObject = (obj: GenericKeyValue, prefix: string = '') =>
      Object.keys(obj).reduce((acc: GenericKeyValue, k: any) => {
        const objPrefix = prefix.length ? prefix + '[' : '';
        const objSuffix = prefix.length ? ']' : '';
        if (typeof obj[k] === 'object') {
          Object.assign(acc, flattenObject(obj[k], objPrefix + k + objSuffix))
        } else {
          acc[objPrefix + k + objSuffix] = obj[k]
        }
        return acc;
      }, {});

    const flattenedData = flattenObject(data);
    const formData = new FormData();
    for (const [key, value] of Object.entries(flattenedData)) {
      formData.set(key, value);
    }

    return formData;
  }

  /**
   * Creates a query string appended to the URL from either a string (returned as is), an array or an object
   *
   * @param {string|array|GenericKeyValue} data
   * @param {string} prefix Internal argument used for nested objects
   * @return {string}
   */
  private static createQueryString(data: string | Array<string> | GenericKeyValue, prefix?: string): string {
    if (typeof data === 'string') {
      return data;
    }

    if (data instanceof Array) {
      return data.join('&');
    }

    return Object.keys(data).map((key: string) => {
      let pKey = prefix ? `${prefix}[${key}]` : key;
      let val = data[key];
      if (typeof val === 'object') {
        return AjaxRequest.createQueryString(val, pKey);
      }

      return `${pKey}=${encodeURIComponent(`${val}`)}`
    }).join('&')
  }

  constructor(url: string) {
    this.url = url;
    this.abortController = new AbortController();

    JQueryNativePromises.support();
  }

  /**
   * Clones the AjaxRequest object, generates the final query string and uses it for the request
   *
   * @param {string|array|GenericKeyValue} data
   * @return {AjaxRequest}
   */
  public withQueryArguments(data: string | Array<string> | GenericKeyValue): AjaxRequest {
    const clone = this.clone();
    clone.queryArguments = (clone.queryArguments !== '' ? '&' : '') + AjaxRequest.createQueryString(data);
    return clone;
  }

  /**
   * Executes a regular GET request
   *
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async get(init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      method: 'GET',
    };

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Executes a (by default uncached) POST request
   *
   * @param {GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async post(data: GenericKeyValue, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      body: AjaxRequest.transformToFormData(data),
      cache: 'no-cache',
      method: 'POST',
    };

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Executes a (by default uncached) PUT request
   *
   * @param {GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async put(data: GenericKeyValue, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      body: AjaxRequest.transformToFormData(data),
      cache: 'no-cache',
      method: 'PUT',
    };

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Executes a regular DELETE request
   *
   * @param {GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async delete(data: GenericKeyValue = {}, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      cache: 'no-cache',
      method: 'DELETE',
    };

    if (typeof data === 'object' && Object.keys(data).length > 0) {
      localDefaultOptions.body = AjaxRequest.transformToFormData(data);
    }

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Gets an instance of AbortController used to abort the current request
   *
   * @return {AbortController}
   */
  public getAbort(): AbortController {
    return this.abortController;
  }

  /**
   * Clones the current AjaxRequest object
   *
   * @return {AjaxRequest}
   */
  private clone(): AjaxRequest {
    return Object.assign(Object.create(this), this);
  }

  /**
   * Sends the requests by using the fetch API
   *
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  private async send(init: RequestInit = {}): Promise<Response> {
    // Sanitize URL into a generic format, e.g. ensure a domain only url contains a trailing slash
    let url = new URL(this.url).toString();
    if (this.queryArguments !== '') {
      const delimiter = !this.url.includes('?') ? '?' : '&';
      url += delimiter + this.queryArguments;
    }
    const response = await fetch(url, this.getMergedOptions(init));
    if (!response.ok) {
      throw new ResponseError(response);
    }
    return response;
  }

  /**
   * Merge the incoming RequestInit object with the pre-defined default options
   *
   * @param {RequestInit} init
   * @return {RequestInit}
   */
  private getMergedOptions(init: RequestInit): RequestInit {
    return {...AjaxRequest.defaultOptions, ...init, signal: this.abortController.signal};
  }
}

export = AjaxRequest;
