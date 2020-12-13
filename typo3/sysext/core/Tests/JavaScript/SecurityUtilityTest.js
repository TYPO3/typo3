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
var __values=this&&this.__values||function(e){var t="function"==typeof Symbol&&e[Symbol.iterator],r=0;return t?t.call(e):{next:function(){return e&&r>=e.length&&(e=void 0),{value:e&&e[r++],done:!e}}}};define(["require","exports","TYPO3/CMS/Core/SecurityUtility"],(function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),describe("TYPO3/CMS/Core/SecurityUtility",(function(){it("generates random hex value",(function(){try{for(var e=__values([1,20,39]),t=e.next();!t.done;t=e.next()){var n=t.value,o=(new r).getRandomHexValue(n);expect(o.length).toBe(n)}}catch(e){a={error:e}}finally{try{t&&!t.done&&(i=e.return)&&i.call(e)}finally{if(a)throw a.error}}var a,i})),it("throws SyntaxError on invalid length",(function(){var e,t,n=function(e){expect((function(){return(new r).getRandomHexValue(e)})).toThrowError(SyntaxError)};try{for(var o=__values([0,-90,10.3]),a=o.next();!a.done;a=o.next()){n(a.value)}}catch(t){e={error:t}}finally{try{a&&!a.done&&(t=o.return)&&t.call(o)}finally{if(e)throw e.error}}})),it("encodes HTML",(function(){expect((new r).encodeHtml("<>\"'&")).toBe("&lt;&gt;&quot;&apos;&amp;")}))}))}));