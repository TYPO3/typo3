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

/**
 * Module: @typo3/core/utility/text-normalizer
 *
 * Intl.Collator is deliberately not used: it compares whole strings rather than
 * searching inside one, its result depends on the locale it is given, and at
 * sensitivity "base" it merges the Japanese for "school" and for "cuckoo".
 *
 * @internal
 */
class TextNormalizer {
  // The marks a script writes optionally, one constant per script so that each
  // range can be read and checked against Unicode on its own. The marks Thai,
  // Devanagari, Khmer, Lao and Japanese write obligatorily are deliberately
  // absent: there a mark separates one word from another rather than accenting
  // it, so removing it would fold the Japanese for "school" and for "cuckoo"
  // into one value and leave a Thai filter narrowing nothing.

  /** Combining diacritical marks, written by Latin, Greek and Cyrillic. */
  private static readonly latinGreekCyrillicMarks = /[\u0300-\u036f]/g;

  /** Hebrew points and cantillation. The gaps are punctuation, not marks. */
  private static readonly hebrewPoints = /[\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7]/g;

  /** Arabic harakat, hamza and madda, plus the superscript alef. */
  private static readonly arabicHarakat = /[\u064b-\u065f\u0670]/g;

  // Latin letters Unicode does not decompose, so stripping marks never reaches
  // them. Without these an alphabet folds inconsistently: Danish "å" becomes "a"
  // because it decomposes, while "æ" and "ø" would stay as they are.
  //
  // Only letters whose ASCII spelling is conventional rather than a matter of
  // language are in here. The transcriptions that are a matter of language --
  // German "ü" to "ue" rather than to "u" -- are not, because the decomposition
  // already settles those.

  /** Their conventional ASCII spelling, applied after the marks are stripped. */
  private static readonly latinExpansions = new Map([
    ['ß', 'ss'], ['æ', 'ae'], ['œ', 'oe'], ['þ', 'th'],
    ['ø', 'o'], ['đ', 'd'], ['ð', 'd'], ['ł', 'l'],
    ['ħ', 'h'], ['ı', 'i'], ['ĸ', 'k'], ['ŋ', 'n'],
    ['ŧ', 't'],
  ]);

  /** Built from the map above so the letters are listed in one place only. */
  private static readonly latinExpansionPattern =
    new RegExp('[' + [...TextNormalizer.latinExpansions.keys()].join('') + ']', 'g');

  /** Characters that take no width, so a reader cannot tell they are there. */
  private static readonly zeroWidthCharacters = /[\u00ad\u200b-\u200d\u2060\ufeff]/g;

  /** The space separators, all compared as a plain U+0020. */
  private static readonly spaceVariants = /[\u00a0\u1680\u2000-\u200a\u202f\u205f\u3000]/g;

  /**
   * Lowercase, strip the optional marks and spell out the Latin letters that do
   * not decompose, so that a filter matches "Crème" when "creme" is typed,
   * "Straße" when "strasse" is typed, and an Arabic label carrying its harakat
   * when it is typed without them.
   *
   * toLocaleLowerCase() is avoided: it follows the browser UI locale, where "I"
   * folds to a dotless "ı".
   */
  public static foldCaseAndDiacritics(value: string): string {
    return value
      .toLowerCase()
      .normalize('NFD')
      .replace(TextNormalizer.latinGreekCyrillicMarks, '')
      .replace(TextNormalizer.hebrewPoints, '')
      .replace(TextNormalizer.arabicHarakat, '')
      .replace(TextNormalizer.latinExpansionPattern, (letter: string): string => TextNormalizer.latinExpansions.get(letter));
  }

  /**
   * Drop the characters a reader cannot see and unify the space variants, so
   * that a label carrying a soft hyphen or a no-break space still matches what
   * is typed. Both arrive in labels by pasting.
   */
  public static normalizeInvisibleCharacters(value: string): string {
    return value
      .replace(TextNormalizer.zeroWidthCharacters, '')
      .replace(TextNormalizer.spaceVariants, ' ');
  }
}

export default TextNormalizer;
