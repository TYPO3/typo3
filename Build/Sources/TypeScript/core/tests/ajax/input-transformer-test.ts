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

import {GenericKeyValue, InputTransformer} from '@typo3/core/ajax/input-transformer';

describe('@typo3/core/ajax/input-transformer', (): void => {
  it('converts object to FormData', (): void => {
    const input: GenericKeyValue = {foo: 'bar', bar: 'baz', nested: {works: 'yes'}};
    const expected = new FormData();
    expected.set('foo', 'bar');
    expected.set('bar', 'baz');
    expected.set('nested[works]', 'yes');

    expect(InputTransformer.toFormData(input)).toEqual(expected);
  });

  it('undefined values are removed in FormData', (): void => {
    const input: GenericKeyValue = {foo: 'bar', bar: 'baz', removeme: undefined};
    const expected = new FormData();
    expected.set('foo', 'bar');
    expected.set('bar', 'baz');

    expect(InputTransformer.toFormData(input)).toEqual(expected);
  });

  it('converts object to SearchParams', (): void => {
    const input: GenericKeyValue = {foo: 'bar', bar: 'baz', nested: {works: 'yes'}};
    const expected = 'foo=bar&bar=baz&nested[works]=yes';

    expect(InputTransformer.toSearchParams(input)).toEqual(expected);
  });

  it('merges array to SearchParams', (): void => {
    const input: Array<string> = ['foo=bar', 'bar=baz'];
    const expected = 'foo=bar&bar=baz';

    expect(InputTransformer.toSearchParams(input)).toEqual(expected);
  });

  it('keeps string in SearchParams', (): void => {
    const input: string = 'foo=bar&bar=baz';
    const expected = 'foo=bar&bar=baz';

    expect(InputTransformer.toSearchParams(input)).toEqual(expected);
  });

  it('undefined values are removed in SearchParams', (): void => {
    const input: GenericKeyValue = {foo: 'bar', bar: 'baz', removeme: undefined};
    const expected = 'foo=bar&bar=baz';
    expect(InputTransformer.toSearchParams(input)).toEqual(expected);
  });
});
