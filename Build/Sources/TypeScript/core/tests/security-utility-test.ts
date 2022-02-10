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

import SecurityUtility from '@typo3/core/security-utility';

describe('@typo3/core/security-utility', (): void => {
  it('generates random hex value', (): void => {
    function* validLengthDataProvider(): any {
      yield 1;
      yield 20;
      yield 39;
    }
    for (let validLength of validLengthDataProvider()) {
      const randomHexValue = (new SecurityUtility()).getRandomHexValue(validLength);
      expect(randomHexValue.length).toBe(validLength);
    }
  });

  it('throws SyntaxError on invalid length', (): void => {
    function* invalidLengthDataProvider(): any {
      yield 0;
      yield -90;
      yield 10.3; // length is "ceiled", 10.3 => 11, 10 != 11
    }
    for (let invalidLength of invalidLengthDataProvider()) {
      expect(() => (new SecurityUtility()).getRandomHexValue(invalidLength)).toThrowError(SyntaxError);
    }
  });

  it('encodes HTML', (): void => {
    expect((new SecurityUtility).encodeHtml('<>"\'&')).toBe('&lt;&gt;&quot;&apos;&amp;');
  });

  it('removes HTML from string', (): void => {
    expect((new SecurityUtility).stripHtml('<img src="" onerror="alert(\'1\')">oh noes')).toBe('oh noes');
    expect((new SecurityUtility).encodeHtml('<>"\'&')).toBe('&lt;&gt;&quot;&apos;&amp;');
  });
});
