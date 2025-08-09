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
import PastedPasswordChecker from '@typo3/backend/login/pasted-password-checker.js';

describe('@typo3/backend/login-test', () => {
  describe('PastedPasswordChecker', () => {
    it('detects passwords without whitespace around them', () => {
      expect(PastedPasswordChecker.hasSurroundingWhitespace('password')).to.be.false;
      expect(PastedPasswordChecker.hasSurroundingWhitespace('password with whitespace inside')).to.be.false;
    });
    it('detects passwords with whitespace around them', () => {
      expect(PastedPasswordChecker.hasSurroundingWhitespace(' passwordWithLeadingSpace')).to.be.true;
      expect(PastedPasswordChecker.hasSurroundingWhitespace('passwordWithTrailingSpace ')).to.be.true;
      expect(PastedPasswordChecker.hasSurroundingWhitespace(' passwordWithSpacesAroundIt ')).to.be.true;
      expect(PastedPasswordChecker.hasSurroundingWhitespace(' password with whitespace inside and around ')).to.be.true;
      expect(PastedPasswordChecker.hasSurroundingWhitespace('passwordWithTrailingNewline\n')).to.be.true;
    });

    it('leaves passwords without whitespace around them intact', () => {
      expect(PastedPasswordChecker.removeSurroundingWhitespace('password')).to.be.equal('password');
      expect(PastedPasswordChecker.removeSurroundingWhitespace('password with whitespace inside')).to.be.equal('password with whitespace inside');
    });

    it('removes leading and trailing whitespace around passwords', () => {
      expect(PastedPasswordChecker.removeSurroundingWhitespace(' passwordWithLeadingSpace')).to.be.equal('passwordWithLeadingSpace');
      expect(PastedPasswordChecker.removeSurroundingWhitespace('passwordWithTrailingSpace ')).to.be.equal('passwordWithTrailingSpace');
      expect(PastedPasswordChecker.removeSurroundingWhitespace(' passwordWithSpacesAroundIt ')).to.be.equal('passwordWithSpacesAroundIt');
      expect(PastedPasswordChecker.removeSurroundingWhitespace('passwordWithTrailingNewline\n')).to.be.equal('passwordWithTrailingNewline');
    });
  });
});
