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
var __awaiter=this&&this.__awaiter||function(t,e,n,o){return new(n||(n=Promise))((function(r,s){function i(t){try{c(o.next(t))}catch(t){s(t)}}function a(t){try{c(o.throw(t))}catch(t){s(t)}}function c(t){var e;t.done?r(t.value):(e=t.value,e instanceof n?e:new n((function(t){t(e)}))).then(i,a)}c((o=o.apply(t,e||[])).next())}))};define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});
/*! Based on https://www.promisejs.org/polyfills/promise-done-7.0.4.js */
class n{static support(){"function"!=typeof Promise.prototype.done&&(Promise.prototype.done=function(t){return arguments.length?this.then.apply(this,arguments):Promise.prototype.then}),"function"!=typeof Promise.prototype.fail&&(Promise.prototype.fail=function(t){return this.catch(e=>__awaiter(this,void 0,void 0,(function*(){const o=e.response;t(yield n.createFakeXhrObject(o),"error",o.statusText)}))),this})}static createFakeXhrObject(t){return __awaiter(this,void 0,void 0,(function*(){const e={readyState:4};return e.responseText=yield t.text(),e.responseURL=t.url,e.status=t.status,e.statusText=t.statusText,t.headers.has("Content-Type")&&t.headers.get("Content-Type").includes("application/json")?(e.responseType="json",e.contentJSON=yield t.json()):e.responseType="text",e}))}}e.default=n}));