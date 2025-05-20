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

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response.js';
import { expect } from '@open-wc/testing';
import { stub, SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/core/ajax/ajax-request', (): void => {
  let promiseHelper: {
    resolve: (response: Response) => void,
    reject: (error: Error) => void
  };

  let fetchStub: SinonStub<Parameters<typeof fetch>, Promise<Response>>;

  beforeEach((): void => {
    const fetchPromise: Promise<Response> = new Promise((resolve, reject): void => {
      promiseHelper = {
        resolve: resolve,
        reject: reject,
      };
    });
    fetchStub = stub(window, 'fetch');
    fetchStub.returns(fetchPromise);
  });

  afterEach((): void => {
    fetchStub.restore();
  });

  it('sends GET request', (): void => {
    (new AjaxRequest('https://example.com')).get();
    expect(fetchStub).calledWithMatch(new Request('https://example.com/', { method: 'GET' }));
  });

  for (const requestMethod of ['POST', 'PUT', 'DELETE']) {
    describe(`send a ${requestMethod} request`, (): void => {
      function* requestDataProvider(): any {
        yield [
          'object as payload',
          requestMethod,
          { foo: 'bar', bar: 'baz', nested: { works: 'yes' } },
          (): FormData => {
            const expected = new FormData();
            expected.set('foo', 'bar');
            expected.set('bar', 'baz');
            expected.set('nested[works]', 'yes');
            return expected;
          },
          {}
        ];
        yield [
          'JSON object as payload',
          requestMethod,
          { foo: 'bar', bar: 'baz', nested: { works: 'yes' } },
          (): string => {
            return JSON.stringify({ foo: 'bar', bar: 'baz', nested: { works: 'yes' } });
          },
          { 'Content-Type': 'application/json' }
        ];
        yield [
          'JSON string as payload',
          requestMethod,
          JSON.stringify({ foo: 'bar', bar: 'baz', nested: { works: 'yes' } }),
          (): string => {
            return JSON.stringify({ foo: 'bar', bar: 'baz', nested: { works: 'yes' } });
          },
          { 'Content-Type': 'application/json' }
        ];
      }

      for (const providedData of requestDataProvider()) {
        const [name, requestMethod, payload, expectedFn, headers] = providedData;
        const requestFn: string = requestMethod.toLowerCase();
        it(`with ${name}`, (done): void => {
          const request: any = (new AjaxRequest('https://example.com'));
          request[requestFn](payload, { headers: headers });
          expect(fetchStub).calledWithMatch(new Request('https://example.com/', { method: requestMethod, body: expectedFn() }));
          done();
        });
      }
    });
  }

  describe('send GET requests', (): void => {
    function* responseDataProvider(): any {
      yield [
        'plaintext',
        'foobar huselpusel',
        {},
        (data: any, responseBody: any): void => {
          expect(typeof data === 'string').to.be.true;
          expect(data).to.be.equal(responseBody);
        }
      ];
      yield [
        'JSON',
        JSON.stringify({ foo: 'bar', baz: 'bencer' }),
        { 'Content-Type': 'application/json' },
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').to.be.true;
          expect(JSON.stringify(data)).to.equal(responseBody);
        }
      ];
      yield [
        'JSON with utf-8',
        JSON.stringify({ foo: 'bar', baz: 'bencer' }),
        { 'Content-Type': 'application/json; charset=utf-8' },
        (data: any, responseBody: any): void => {
          expect(typeof data === 'object').to.be.true;
          expect(JSON.stringify(data)).to.equal(responseBody);
        }
      ];
    }

    for (const providedData of responseDataProvider()) {
      const [name, responseText, headers, onfulfill] = providedData;
      it('receives a ' + name + ' response', (done): void => {
        const response = new Response(responseText, { headers: headers });
        promiseHelper.resolve(response);

        (new AjaxRequest('https://example.com')).get().then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          expect(fetchStub).calledWithMatch(new Request('https://example.com/', { method: 'GET' }));
          onfulfill(data, responseText);
          done();
        });
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
        { foo: 'bar', bar: { baz: 'bencer' } },
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
        { foo: 'bar', bar: { baz: 'bencer' } },
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
        { foo: 'bar', bar: { baz: 'bencer' } },
        window.location.origin + '/foo/bar?foo=bar&bar[baz]=bencer',
      ];
      yield [
        'fallback to current script if not defined',
        '?foo=bar&baz=bencer',
        {},
        window.location.origin + window.location.pathname + '?foo=bar&baz=bencer',
      ];
    }

    for (const providedData of urlInputDataProvider()) {
      const [name, input, queryParameter, expected] = providedData;
      it('with ' + name, (): void => {
        (new AjaxRequest(input)).withQueryArguments(queryParameter).get();
        expect(fetchStub).calledWithMatch(new Request(expected.toString(), { method: 'GET' }));
      });
    }
  });

  describe('send requests with query arguments', (): void => {
    function* queryArgumentsDataProvider(): any {
      yield [
        'single level of arguments',
        { foo: 'bar', bar: 'baz' },
        'https://example.com/?foo=bar&bar=baz',
      ];
      yield [
        'nested arguments',
        { foo: 'bar', bar: { baz: 'bencer' } },
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
      ];
      yield [
        'object with array',
        { foo: ['bar', 'baz'] },
        'https://example.com/?foo[0]=bar&foo[1]=baz',
      ];
      yield [
        'complex object',
        {
          foo: 'bar',
          nested: {
            husel: 'pusel',
            bar: 'baz',
            array: ['5', '6']
          },
          array: ['1', '2']
        },
        'https://example.com/?foo=bar&nested[husel]=pusel&nested[bar]=baz&nested[array][0]=5&nested[array][1]=6&array[0]=1&array[1]=2',
      ];
      yield [
        'complex, deeply nested object',
        {
          foo: 'bar',
          nested: {
            husel: 'pusel',
            bar: 'baz',
            array: ['5', '6'],
            deep_nested: {
              husel: 'pusel',
              bar: 'baz',
              array: ['5', '6']
            },
          },
          array: ['1', '2']
        },
        'https://example.com/?foo=bar&nested[husel]=pusel&nested[bar]=baz&nested[array][0]=5&nested[array][1]=6&nested[deep_nested][husel]=pusel&nested[deep_nested][bar]=baz&nested[deep_nested][array][0]=5&nested[deep_nested][array][1]=6&array[0]=1&array[1]=2',
      ];
    }

    for (const providedData of queryArgumentsDataProvider()) {
      const [name, input, expected] = providedData;
      it('with ' + name, (): void => {
        (new AjaxRequest('https://example.com/')).withQueryArguments(input).get();
        expect(fetchStub).calledWithMatch(new Request(expected.toString(), { method: 'GET' }));
      });
    }
  });

  describe('Aborts requests', (): void => {
    it('via abort() method', (): void => {
      const request = new AjaxRequest('https://example.com');
      request.get();
      request.abort();
      expect((fetchStub.firstCall.args[0] as Request).signal.aborted).to.be.true;
    });

    it('via signal option', (): void => {
      const abortController = new AbortController();
      const request = new AjaxRequest('https://example.com');
      request.get({ signal: abortController.signal });
      abortController.abort();
      expect(abortController.signal.aborted).to.be.true;
      expect((fetchStub.firstCall.args[0] as Request).signal.aborted).to.be.true;
    });
  });
});
