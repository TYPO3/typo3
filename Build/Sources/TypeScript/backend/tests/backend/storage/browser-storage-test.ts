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

import BrowserSession from '@typo3/backend/storage/browser-session.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/storage/browser-session', () => {
  afterEach((): void => {
    BrowserSession.clear();
  });

  it('can set and get item', () => {
    const key = 'test-key';
    BrowserSession.set(key, 'foo');
    expect(BrowserSession.get(key)).to.equal('foo');
  });

  it('can check if item is set', () => {
    const key = 'test-key';
    expect(BrowserSession.isset(key)).to.be.false;
    BrowserSession.set(key, 'foo');
    expect(BrowserSession.isset(key)).to.be.true;
  });

  it('can get multiple items by prefix', () => {
    const entries = {
      'test-prefix-foo': 'foo',
      'test-prefix-bar': 'bar',
      'test-prefix-baz': 'baz',
    };
    for (const [key, value] of Object.entries(entries)) {
      BrowserSession.set(key, value);
    }

    const items = BrowserSession.getByPrefix('test-prefix-');
    expect(items).to.eql(entries);
  });

  it('can remove item', () => {
    const key = 'item-to-be-removed';
    BrowserSession.set(key, 'foo');
    expect(BrowserSession.get(key)).not.to.be.null;

    BrowserSession.unset(key);
    expect(BrowserSession.get(key)).to.be.null;
  });

  it('can remove multiple items by prefix', () => {
    const entries = {
      'test-prefix-foo': 'foo',
      'test-prefix-bar': 'bar',
      'test-prefix-baz': 'baz',
    };
    for (const [key, value] of Object.entries(entries)) {
      BrowserSession.set(key, value);
    }
    BrowserSession.unsetByPrefix('test-prefix-');

    const items = BrowserSession.getByPrefix('test-prefix-');
    expect(items).to.be.empty;
  });

  it('can clear storage', () => {
    const entries = {
      'foo': 'foo',
      'baz': 'bencer',
      'huselpusel': '42',
    };
    for (const [key, value] of Object.entries(entries)) {
      BrowserSession.set(key, value);
    }
    BrowserSession.clear();

    expect(sessionStorage).to.be.empty;
  });
});
