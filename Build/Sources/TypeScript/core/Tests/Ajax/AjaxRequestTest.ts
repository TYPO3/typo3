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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';

describe('TYPO3/CMS/Core/Ajax/AjaxRequest', (): void => {
  let promiseHelper: any;

  beforeEach((): void => {
    const fetchPromise: Promise<Response> = new Promise(((resolve: Function, reject: Function): void => {
      promiseHelper = {
        resolve: resolve,
        reject: reject,
      }
    }));
    spyOn(window, 'fetch').and.returnValue(fetchPromise);
  });

  it('sends GET request', (): void => {
    (new AjaxRequest('https://example.com')).get();
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'GET'}));
  });

  it('sends POST request with object as payload', (): void => {
    const payload = {foo: 'bar', bar: 'baz', nested: {works: 'yes'}};
    const expected = new FormData();
    expected.set('foo', 'bar');
    expected.set('bar', 'baz');
    expected.set('nested[works]', 'yes');
    (new AjaxRequest('https://example.com')).post(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'POST', body: expected}));
  });

  it('sends POST request with string as payload', (): void => {
    const payload = JSON.stringify({foo: 'bar', bar: 'baz', nested: {works: 'yes'}});
    (new AjaxRequest('https://example.com')).post(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'POST', body: payload}));
  });

  it('sends PUT request with object as payload', (): void => {
    const payload = {foo: 'bar', bar: 'baz', nested: {works: 'yes'}};
    const expected = new FormData();
    expected.set('foo', 'bar');
    expected.set('bar', 'baz');
    expected.set('nested[works]', 'yes');
    (new AjaxRequest('https://example.com')).put(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'PUT', body: expected}));
  });

  it('sends PUT request with string as payload', (): void => {
    const payload = JSON.stringify({foo: 'bar', bar: 'baz', nested: {works: 'yes'}});
    (new AjaxRequest('https://example.com')).put(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'PUT', body: payload}));
  });

  it('sends DELETE request', (): void => {
    (new AjaxRequest('https://example.com')).delete();
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'DELETE'}));
  });

  it('sends DELETE request with string as payload', (): void => {
    const payload = JSON.stringify({foo: 'bar', bar: 'baz', nested: {works: 'yes'}});
    (new AjaxRequest('https://example.com')).delete(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'DELETE', body: payload}));
  });

  describe('send GET requests', (): void => {
    function* responseDataProvider(): any {
      yield [
        'plaintext',
        'foobar huselpusel',
        {},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'string').toBeTruthy();
          expect(data).toEqual(responseBody);
        }
      ];
      yield [
        'JSON',
        JSON.stringify({foo: 'bar', baz: 'bencer'}),
        {'Content-Type': 'application/json'},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').toBeTruthy();
          expect(JSON.stringify(data)).toEqual(responseBody);
        }
      ];
      yield [
        'JSON with utf-8',
        JSON.stringify({foo: 'bar', baz: 'bencer'}),
        {'Content-Type': 'application/json; charset=utf-8'},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').toBeTruthy();
          expect(JSON.stringify(data)).toEqual(responseBody);
        }
      ];
    }

    for (let providedData of responseDataProvider()) {
      let [name, responseText, headers, onfulfill] = providedData;
      it('receives a ' + name + ' response', (done: DoneFn): void => {
        const response = new Response(responseText, {headers: headers});
        promiseHelper.resolve(response);

        (new AjaxRequest('https://example.com')).get().then(async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'GET'}));
          onfulfill(data, responseText);
          done();
        })
      });
    }
  });

  describe('send requests with different input urls', (): void => {
    function* urlInputDataProvider(): any {
      yield [
        'absolute url with domain',
        'https://example.com',
        {},
        'https://example.com/',
      ];
      yield [
        'absolute url with domain, with query parameter',
        'https://example.com',
        {foo: 'bar', bar: {baz: 'bencer'}},
        'https://example.com/?foo=bar&bar[baz]=bencer',
      ];
      yield [
        'absolute url without domain',
        '/foo/bar',
        {},
        window.location.origin + '/foo/bar',
      ];
      yield [
        'absolute url without domain, with query parameter',
        '/foo/bar',
        {foo: 'bar', bar: {baz: 'bencer'}},
        window.location.origin + '/foo/bar?foo=bar&bar[baz]=bencer',
      ];
      yield [
        'relative url without domain',
        'foo/bar',
        {},
        window.location.origin + '/foo/bar',
      ];
      yield [
        'relative url without domain, with query parameter',
        'foo/bar',
        {foo: 'bar', bar: {baz: 'bencer'}},
        window.location.origin + '/foo/bar?foo=bar&bar[baz]=bencer',
      ];
    }

    for (let providedData of urlInputDataProvider()) {
      let [name, input, queryParameter, expected] = providedData;
      it('with ' + name, (): void => {
        (new AjaxRequest(input)).withQueryArguments(queryParameter).get();
        expect(window.fetch).toHaveBeenCalledWith(expected, jasmine.objectContaining({method: 'GET'}));
      });
    }
  });

  describe('send requests with query arguments', (): void => {
    function* queryArgumentsDataProvider(): any {
      yield [
        'single level of arguments',
        {foo: 'bar', bar: 'baz'},
        'https://example.com/?foo=bar&bar=baz',
      ];
      yield [
        'nested arguments',
        {foo: 'bar', bar: {baz: 'bencer'}},
        'https://example.com/?foo=bar&bar[baz]=bencer',
      ];
      yield [
        'string argument',
        'hello=world&foo=bar',
        'https://example.com/?hello=world&foo=bar',
      ];
      yield [
        'array of arguments',
        ['foo=bar', 'husel=pusel'],
        'https://example.com/?foo=bar&husel=pusel',
      ]
    }

    for (let providedData of queryArgumentsDataProvider()) {
      let [name, input, expected] = providedData;
      it('with ' + name, (): void => {
        (new AjaxRequest('https://example.com/')).withQueryArguments(input).get();
        expect(window.fetch).toHaveBeenCalledWith(expected, jasmine.objectContaining({method: 'GET'}));
      });
    }
  });
});
