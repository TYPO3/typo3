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
define(["require","exports","TYPO3/CMS/Core/SecurityUtility"],(function(e,t,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),describe("TYPO3/CMS/Core/SecurityUtility",()=>{it("generates random hex value",()=>{for(let e of function*(){yield 1,yield 20,yield 39}()){const t=(new o).getRandomHexValue(e);expect(t.length).toBe(e)}}),it("throws SyntaxError on invalid length",()=>{for(let e of function*(){yield 0,yield-90,yield 10.3}())expect(()=>(new o).getRandomHexValue(e)).toThrowError(SyntaxError)}),it("encodes HTML",()=>{expect((new o).encodeHtml("<>\"'&")).toBe("&lt;&gt;&quot;&apos;&amp;")}),it("removes HTML from string",()=>{expect((new o).stripHtml('<img src="" onerror="alert(\'1\')">oh noes')).toBe("oh noes"),expect((new o).encodeHtml("<>\"'&")).toBe("&lt;&gt;&quot;&apos;&amp;")})})}));