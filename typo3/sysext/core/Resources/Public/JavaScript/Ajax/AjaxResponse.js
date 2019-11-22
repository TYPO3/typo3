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
var __awaiter=this&&this.__awaiter||function(e,t,n,s){return new(n||(n=Promise))((function(r,i){function o(e){try{c(s.next(e))}catch(e){i(e)}}function a(e){try{c(s.throw(e))}catch(e){i(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,a)}c((s=s.apply(e,t||[])).next())}))};define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0});t.AjaxResponse=class{constructor(e){this.response=e}resolve(){return __awaiter(this,void 0,void 0,(function*(){return this.response.headers.has("Content-Type")&&this.response.headers.get("Content-Type").includes("application/json")?yield this.response.json():yield this.response.text()}))}raw(){return this.response}}}));