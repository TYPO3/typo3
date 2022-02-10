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

import {BackendException} from '@typo3/backend/backend-exception';

describe('@typo3/backend/backend-exception', () => {
  it('sets exception message', () => {
    const backendException: BackendException = new BackendException('some message');
    expect(backendException.message).toBe('some message');
  });

  it('sets exception code', () => {
    const backendException: BackendException = new BackendException('', 12345);
    expect(backendException.code).toBe(12345);
  });
});
