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
import SecurityUtility from"@typo3/core/security-utility.js";describe("@typo3/core/security-utility",()=>{it("generates random hex value",()=>{for(let t of function*(){yield 1,yield 20,yield 39}()){const e=(new SecurityUtility).getRandomHexValue(t);expect(e.length).toBe(t)}}),it("throws SyntaxError on invalid length",()=>{for(let t of function*(){yield 0,yield-90,yield 10.3}())expect(()=>(new SecurityUtility).getRandomHexValue(t)).toThrowError(SyntaxError)}),it("encodes HTML",()=>{expect((new SecurityUtility).encodeHtml("<>\"'&")).toBe("&lt;&gt;&quot;&apos;&amp;")}),it("removes HTML from string",()=>{expect((new SecurityUtility).stripHtml('<img src="" onerror="alert(\'1\')">oh noes')).toBe("oh noes"),expect((new SecurityUtility).encodeHtml("<>\"'&")).toBe("&lt;&gt;&quot;&apos;&amp;")})});