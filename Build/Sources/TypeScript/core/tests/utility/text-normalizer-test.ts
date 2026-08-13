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

import TextNormalizer from '@typo3/core/utility/text-normalizer.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/core/utility/text-normalizer', () => {
  describe('foldCaseAndDiacritics', () => {
    it('returns an empty string unchanged', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('')).to.equal('');
    });

    it('lowercases the value', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Cremeschnitte')).to.equal('cremeschnitte');
    });

    it('removes Latin diacritics', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Crème brûlée')).to.equal('creme brulee');
      expect(TextNormalizer.foldCaseAndDiacritics('Müller')).to.equal('muller');
    });

    it('removes Greek and Cyrillic diacritics', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('ΆΘΗΝΑ')).to.equal('αθηνα');
      expect(TextNormalizer.foldCaseAndDiacritics('Ёлка')).to.equal('елка');
    });

    it('removes every Vietnamese tone mark', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Tiếng Việt')).to.equal('tieng viet');
    });

    it('folds a value that is already decomposed to the same result', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Crème'.normalize('NFD'))).to.equal(TextNormalizer.foldCaseAndDiacritics('Crème'.normalize('NFC')));
    });

    // A locale aware mapping folds this to a dotless "ı" on a Turkish browser.
    it('folds the capital i to a plain lowercase i', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Import')).to.equal('import');
      expect(TextNormalizer.foldCaseAndDiacritics('İstanbul')).to.equal('istanbul');
    });

    it('spells out the Latin letters that do not decompose', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('Straße')).to.equal('strasse');
      expect(TextNormalizer.foldCaseAndDiacritics('Ærø')).to.equal('aero');
      expect(TextNormalizer.foldCaseAndDiacritics('Łódź')).to.equal('lodz');
      expect(TextNormalizer.foldCaseAndDiacritics('Þórður')).to.equal('thordur');
      expect(TextNormalizer.foldCaseAndDiacritics('Đà Nẵng')).to.equal('da nang');
      expect(TextNormalizer.foldCaseAndDiacritics('Ħamrun')).to.equal('hamrun');
      expect(TextNormalizer.foldCaseAndDiacritics('Cœur')).to.equal('coeur');
      expect(TextNormalizer.foldCaseAndDiacritics('Kız')).to.equal('kiz');
    });

    it('folds an alphabet consistently rather than half of it', (): void => {
      // Danish: "å" decomposes and "æ"/"ø" do not, so without the expansions one
      // half of the alphabet would fold and the other half would not.
      expect(TextNormalizer.foldCaseAndDiacritics('Åse')).to.equal('ase');
      expect(TextNormalizer.foldCaseAndDiacritics('Æble')).to.equal('aeble');
      expect(TextNormalizer.foldCaseAndDiacritics('Øst')).to.equal('ost');
    });

    it('leaves the language dependent transcriptions alone', (): void => {
      // "ü" decomposes to "u", so the German transcription to "ue" is not applied
      // on top of it -- a value cannot fold to both.
      expect(TextNormalizer.foldCaseAndDiacritics('Müller')).to.equal('muller');
      expect(TextNormalizer.foldCaseAndDiacritics('Müller')).to.not.equal('mueller');
    });

    it('removes the Hebrew points', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('\u05e9\u05c1\u05b8\u05dc\u05d5\u05b9\u05dd'))
        .to.equal(TextNormalizer.foldCaseAndDiacritics('\u05e9\u05dc\u05d5\u05dd'));
    });

    it('removes the Arabic harakat', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('\u0645\u064e\u062f\u0652\u0631\u064e\u0633\u064e\u0629'))
        .to.equal(TextNormalizer.foldCaseAndDiacritics('\u0645\u062f\u0631\u0633\u0629'));
    });

    it('folds the Arabic alef variants onto the plain alef', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('\u0623\u062d\u0645\u062f'))
        .to.equal(TextNormalizer.foldCaseAndDiacritics('\u0627\u062d\u0645\u062f'));
    });

    it('keeps the marks a script writes obligatorily', (): void => {
      // Thai vowel signs, Devanagari matras and the Japanese dakuten separate
      // words rather than accent them, so folding must leave them alone.
      const distinct = (values: string[]): number =>
        new Set(values.map((value: string): string => TextNormalizer.foldCaseAndDiacritics(value))).size;
      expect(distinct(['\u0e01\u0e34', '\u0e01\u0e35', '\u0e01\u0e38'])).to.equal(3);
      expect(distinct(['\u0915\u093f', '\u0915\u0941', '\u0915\u0947'])).to.equal(3);
      expect(distinct(['\u304b', '\u304c', '\u3071', '\u3070'])).to.equal(4);
    });

    it('leaves Chinese untouched and unifies the compatibility ideographs', (): void => {
      expect(TextNormalizer.foldCaseAndDiacritics('\u4e2d\u6587')).to.equal('\u4e2d\u6587');
      expect(TextNormalizer.foldCaseAndDiacritics('\uf900')).to.equal('\u8c48');
    });
  });

  describe('normalizeInvisibleCharacters', () => {
    it('returns an empty string unchanged', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('')).to.equal('');
    });

    it('drops a soft hyphen', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('Do\u00adnau')).to.equal('Donau');
    });

    it('drops the zero width characters', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('a\u200bb\u200cc\u200dd\u2060e\ufeff')).to.equal('abcde');
    });

    it('turns a no-break space into a plain space', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('Cr\u00e8me\u00a0br\u00fbl\u00e9e')).to.equal('Cr\u00e8me br\u00fbl\u00e9e');
    });

    it('turns the other space variants into a plain space', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('a\u2009b\u202fc\u3000d')).to.equal('a b c d');
    });

    it('leaves an ordinary label untouched', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('Cremeschnitte')).to.equal('Cremeschnitte');
    });

    it('leaves casing and diacritics to the other function', (): void => {
      expect(TextNormalizer.normalizeInvisibleCharacters('Cr\u00e8me Br\u00fbl\u00e9e')).to.equal('Cr\u00e8me Br\u00fbl\u00e9e');
    });
  });
});
