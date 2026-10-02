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

import { writeAdditionalElementPropertyPaths } from '@typo3/form/backend/form-editor/additional-element-property-paths.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

type Call = ['set', string, unknown] | ['unset', string];

function createFormElement() {
  const calls: Call[] = [];
  return {
    calls,
    set(key: string, value: unknown): void {
      calls.push(['set', key, value]);
    },
    unset(key: string): void {
      calls.push(['unset', key]);
    },
  };
}

describe('@typo3/form/backend/form-editor/additional-element-property-paths', () => {
  it('does nothing without additional property paths', () => {
    const formElement = createFormElement();

    writeAdditionalElementPropertyPaths(formElement, { doNotSetIfPropertyValueIsEmpty: true }, '', true);

    expect(formElement.calls).to.deep.equal([]);
  });

  it('sets the value on every additional property path', () => {
    const formElement = createFormElement();

    writeAdditionalElementPropertyPaths(
      formElement,
      { additionalElementPropertyPaths: ['properties.a', 'properties.b'], doNotSetIfPropertyValueIsEmpty: true },
      'foo',
      false
    );

    expect(formElement.calls).to.deep.equal([
      ['set', 'properties.a', 'foo'],
      ['set', 'properties.b', 'foo'],
    ]);
  });

  it('unsets every additional property path for an empty value if doNotSetIfPropertyValueIsEmpty is set', () => {
    const formElement = createFormElement();

    writeAdditionalElementPropertyPaths(
      formElement,
      { additionalElementPropertyPaths: ['properties.a', 'properties.b'], doNotSetIfPropertyValueIsEmpty: true },
      '',
      true
    );

    expect(formElement.calls).to.deep.equal([
      ['unset', 'properties.a'],
      ['unset', 'properties.b'],
    ]);
  });

  it('sets an empty value if doNotSetIfPropertyValueIsEmpty is not set', () => {
    const formElement = createFormElement();

    writeAdditionalElementPropertyPaths(
      formElement,
      { additionalElementPropertyPaths: ['properties.a'] },
      '',
      true
    );

    expect(formElement.calls).to.deep.equal([
      ['set', 'properties.a', ''],
    ]);
  });
});
