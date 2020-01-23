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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});
/*! Based on https://www.promisejs.org/polyfills/promise-done-7.0.4.js */
class s{static support(){"function"!=typeof Promise.prototype.done&&(Promise.prototype.done=function(t){return arguments.length?this.then.apply(this,arguments):Promise.prototype.then}),"function"!=typeof Promise.prototype.fail&&(Promise.prototype.fail=function(t){return this.catch(async e=>{const o=e.response;t(await s.createFakeXhrObject(o),"error",o.statusText)}),this})}static async createFakeXhrObject(t){const e={readyState:4};return e.responseText=await t.text(),e.responseURL=t.url,e.status=t.status,e.statusText=t.statusText,t.headers.has("Content-Type")&&t.headers.get("Content-Type").includes("application/json")?(e.responseType="json",e.contentJSON=await t.json()):e.responseType="text",e}}e.default=s}));