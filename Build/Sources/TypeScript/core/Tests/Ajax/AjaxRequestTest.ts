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

  it('sends POST request', (): void => {
    const payload = {foo: 'bar', bar: 'baz', nested: {works: 'yes'}};
    const expected = new FormData();
    expected.set('foo', 'bar');
    expected.set('bar', 'baz');
    expected.set('nested[works]', 'yes');
    (new AjaxRequest('https://example.com')).post(payload);
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'POST', body: expected}));
  });

  it('sends PUT request', (): void => {
    (new AjaxRequest('https://example.com')).put({});
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'PUT'}));
  });

  it('sends DELETE request', (): void => {
    (new AjaxRequest('https://example.com')).delete();
    expect(window.fetch).toHaveBeenCalledWith('https://example.com/', jasmine.objectContaining({method: 'DELETE'}));
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
          expect(window.fetch).toHaveBeenCalledWith(jasmine.any(String), jasmine.objectContaining({method: 'GET'}));
        }
      ];
      yield [
        'JSON',
        JSON.stringify({foo: 'bar', baz: 'bencer'}),
        {'Content-Type': 'application/json'},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').toBeTruthy();
          expect(JSON.stringify(data)).toEqual(responseBody);
          expect(window.fetch).toHaveBeenCalledWith(jasmine.any(String), jasmine.objectContaining({method: 'GET'}));
        }
      ];
      yield [
        'JSON with utf-8',
        JSON.stringify({foo: 'bar', baz: 'bencer'}),
        {'Content-Type': 'application/json; charset=utf-8'},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').toBeTruthy();
          expect(JSON.stringify(data)).toEqual(responseBody);
          expect(window.fetch).toHaveBeenCalledWith(jasmine.any(String), jasmine.objectContaining({method: 'GET'}));
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
          onfulfill(data, responseText);
          done();
        })
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
        (new AjaxRequest('https://example.com')).withQueryArguments(input).get();
        expect(window.fetch).toHaveBeenCalledWith(expected, jasmine.objectContaining({method: 'GET'}));
      });
    }
  });
});
