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
var __values=this&&this.__values||function(e){var r="function"==typeof Symbol&&e[Symbol.iterator],t=0;return r?r.call(e):{next:function(){return e&&t>=e.length&&(e=void 0),{value:e&&e[t++],done:!e}}}};define(["require","exports","TYPO3/CMS/Core/SecurityUtility"],function(e,r,t){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),describe("TYPO3/CMS/Core/SecurityUtility",function(){it("generates random hex value",function(){try{for(var e=__values([1,20,39]),r=e.next();!r.done;r=e.next()){var n=r.value,o=(new t).getRandomHexValue(n);expect(o.length).toBe(n)}}catch(e){a={error:e}}finally{try{r&&!r.done&&(i=e.return)&&i.call(e)}finally{if(a)throw a.error}}var a,i}),it("throws SyntaxError on invalid length",function(){var e,r,n=function(e){expect(function(){return(new t).getRandomHexValue(e)}).toThrowError(SyntaxError)};try{for(var o=__values([0,-90,10.3]),a=o.next();!a.done;a=o.next()){n(a.value)}}catch(r){e={error:r}}finally{try{a&&!a.done&&(r=o.return)&&r.call(o)}finally{if(e)throw e.error}}})})});