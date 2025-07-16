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

import { expect } from '@open-wc/testing';
import { UrlFactory } from '@typo3/core/factory/url-factory.js';
import type { URLSearchParamsFeedable } from '@typo3/core/factory/url-factory';

describe('@typo3/backend/factory/url-factory', (): void => {
  it('creates URL from string', (): void => {
    const input = 'https://localhost:9000/hello?who=world';
    const url = UrlFactory.createUrl(input);

    expect(url).to.be.instanceof(URL);
    expect(url.toString()).to.be.equal(input);
  });

  it('creates URL from URL', (): void => {
    const input = new URL('https://localhost:9000/hello?who=world');
    const url = UrlFactory.createUrl(input);

    expect(url).to.be.instanceof(URL);
    expect(url.toString()).to.be.equal(input.toString());
  });

  describe('creates URL with parameters', (): void => {
    function* queryArgumentsDataProvider(): Generator<[string, URLSearchParamsFeedable, string]> {
      yield [
        'single level of arguments',
        { foo: 'bar', bar: 'baz' },
        'https://localhost/?foo=bar&bar=baz',
      ];
      yield [
        'nested level of arguments',
        { foo: 'bar', bar: { baz: 'bencer' } },
        'https://localhost/?foo=bar&bar%5Bbaz%5D=bencer',
      ];
      yield [
        'arguments with array as value',
        { foo: ['bar', 'baz'] },
        'https://localhost/?foo%5B0%5D=bar&foo%5B1%5D=baz',
      ];
      yield [
        'arguments with array containing object',
        { foo: ['bar', { baz: 'bencer' }] },
        'https://localhost/?foo%5B0%5D=bar&foo%5B1%5D%5Bbaz%5D=bencer',
      ];
      yield [
        'undefined arguments',
        { foo: 'bar', bar: { baz: undefined, qux: null } },
        'https://localhost/?foo=bar',
      ];
      yield [
        'string argument',
        'foo=bar',
        'https://localhost/?foo=bar',
      ];
    }

    for (const providedData of queryArgumentsDataProvider()) {
      const [name, input, expected] = providedData;
      it('with ' + name, (): void => {
        const url = UrlFactory.createUrl('https://localhost', input);
        expect(url).to.be.instanceof(URL);
        expect(url.toString()).to.be.equal(expected);
      });
    }
  });

  describe('creates URL and extends query string', (): void => {
    function* queryArgumentsDataProvider(): Generator<[string, string, URLSearchParamsFeedable, string]> {
      yield [
        'new parameter name as string',
        'https://localhost/?foo=bar',
        'bar=baz',
        'https://localhost/?foo=bar&bar=baz',
      ];
      yield [
        'new parameter name as object',
        'https://localhost/?foo=bar',
        { bar: 'baz' },
        'https://localhost/?foo=bar&bar=baz',
      ];
    }

    for (const providedData of queryArgumentsDataProvider()) {
      const [name, inputUrl, inputArguments, expected] = providedData;

      it('with ' + name, (): void => {
        const url = UrlFactory.createUrl(inputUrl, inputArguments);
        expect(url).to.be.instanceof(URL);
        expect(url.toString()).to.be.equal(expected);
      });
    }
  });
});
