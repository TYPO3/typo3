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

import {AjaxResponse} from './ajax-response';
import {GenericKeyValue, InputTransformer} from './input-transformer';

/**
 * @example send data as `Content-Type: multipart/form-data` (default)
 * const request = new AjaxRequest('/endpoint');
 * const response = request.post({item: { first: 'value'}});
 * // sends `item[first]=value` in request body (analogous)
 * // can be used like this server-side
 * // `$value = ServerRequest::getParsedBody()['item']['first']`
 *
 * @example send data as `Content-Type: application/json` (must be set explicit)
 * const request = new AjaxRequest('/endpoint');
 * const response = request.post({item: { first: 'value'}});
 * // sends `{"item":{"first":"value"}}` in request body
 * // can be used like this server-side
 * // `$value = json_decode((string)ServerRequest::getBody(), true)['item']['first']`
 */
class AjaxRequest {
  private static defaultOptions: RequestInit = {
    credentials: 'same-origin'
  };

  private readonly url: string;
  private readonly abortController: AbortController;
  private queryArguments: string = '';

  constructor(url: string) {
    this.url = url;
    this.abortController = new AbortController();
  }

  /**
   * Clones the AjaxRequest object, generates the final query string and uses it for the request
   *
   * @param {string|array|GenericKeyValue} data
   * @return {AjaxRequest}
   */
  public withQueryArguments(data: string | Array<string> | GenericKeyValue): AjaxRequest {
    const clone = this.clone();
    clone.queryArguments = (clone.queryArguments !== '' ? '&' : '') + InputTransformer.toSearchParams(data);
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
   * @param {string | GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async post(data: string | GenericKeyValue, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      body: typeof data === 'string' ? data : InputTransformer.byHeader(data, init?.headers),
      cache: 'no-cache',
      method: 'POST',
    };

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Executes a (by default uncached) PUT request
   *
   * @param {string | GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async put(data: string | GenericKeyValue, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      body: typeof data === 'string' ? data : InputTransformer.byHeader(data, init?.headers),
      cache: 'no-cache',
      method: 'PUT',
    };

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Executes a regular DELETE request
   *
   * @param {string | GenericKeyValue} data
   * @param {RequestInit} init
   * @return {Promise<Response>}
   */
  public async delete(data: string | GenericKeyValue = {}, init: RequestInit = {}): Promise<AjaxResponse> {
    const localDefaultOptions: RequestInit = {
      cache: 'no-cache',
      method: 'DELETE',
    };

    if (typeof data === 'object' && Object.keys(data).length > 0) {
      localDefaultOptions.body = InputTransformer.byHeader(data, init?.headers);
    } else if (typeof data === 'string' && data.length > 0) {
      localDefaultOptions.body = data;
    }

    const response = await this.send({...localDefaultOptions, ...init});
    return new AjaxResponse(response);
  }

  /**
   * Aborts the current request by using the AbortController
   */
  public abort(): void {
    this.abortController.abort();
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
    const response = await fetch(this.composeRequestUrl(), this.getMergedOptions(init));
    if (!response.ok) {
      throw new AjaxResponse(response);
    }
    return response;
  }

  private composeRequestUrl(): string {
    let url = this.url;
    if (url.charAt(0) === '?') {
      // URL is a search string only, prepend current location
      url = window.location.origin + window.location.pathname + url;
    }
    url = new URL(url, window.location.origin).toString();

    if (this.queryArguments !== '') {
      const delimiter = !this.url.includes('?') ? '?' : '&';
      url += delimiter + this.queryArguments;
    }

    return url;
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

export default AjaxRequest;
