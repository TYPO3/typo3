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
class a{static{this.latinGreekCyrillicMarks=/[\u0300-\u036f]/g}static{this.hebrewPoints=/[\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7]/g}static{this.arabicHarakat=/[\u064b-\u065f\u0670]/g}static{this.latinExpansions=new Map([["\xDF","ss"],["\xE6","ae"],["\u0153","oe"],["\xFE","th"],["\xF8","o"],["\u0111","d"],["\xF0","d"],["\u0142","l"],["\u0127","h"],["\u0131","i"],["\u0138","k"],["\u014B","n"],["\u0167","t"]])}static{this.latinExpansionPattern=new RegExp("["+[...a.latinExpansions.keys()].join("")+"]","g")}static{this.zeroWidthCharacters=/[\u00ad\u200b-\u200d\u2060\ufeff]/g}static{this.spaceVariants=/[\u00a0\u1680\u2000-\u200a\u202f\u205f\u3000]/g}static foldCaseAndDiacritics(t){return t.toLowerCase().normalize("NFD").replace(a.latinGreekCyrillicMarks,"").replace(a.hebrewPoints,"").replace(a.arabicHarakat,"").replace(a.latinExpansionPattern,s=>a.latinExpansions.get(s))}static normalizeInvisibleCharacters(t){return t.replace(a.zeroWidthCharacters,"").replace(a.spaceVariants," ")}}export{a as default};
