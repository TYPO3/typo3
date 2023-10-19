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

import { selector } from '@typo3/core/literals.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/core/literals', (): void => {
  it('escapes values in query selectors', (): void => {
    const name = 'abc123"#_.,xyz';
    const value = selector`#field[name="${name}"]`;
    expect(value).to.equal('#field[name="abc123\\"\\#_\\.\\,xyz"]');
  });
});
