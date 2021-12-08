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

import Md5 from 'TYPO3/CMS/Backend/Hashing/Md5';

describe('TYPO3/CMS/Backend/Hashing/Md5:', () => {
  describe('tests for hash', () => {
    it('hashes a value as expected', () => {
      expect(Md5.hash('Hello World')).toBe('b10a8db164e0754105b7a99be72e3fe5');
      expect(
        Md5.hash('TYPO3 CMS is an Open Source Enterprise Content Management System with a large global community,'
          + ' backed by the approximately 900 members of the TYPO3 Association.'),
      ).toBe('65b0beb76ada01bd7b5f44fb37da6139');
    });
  });
});
