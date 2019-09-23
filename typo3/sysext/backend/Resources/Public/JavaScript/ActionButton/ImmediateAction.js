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
var __awaiter=this&&this.__awaiter||function(t,e,n,r){return new(n||(n=Promise))(function(c,i){function a(t){try{o(r.next(t))}catch(t){i(t)}}function u(t){try{o(r.throw(t))}catch(t){i(t)}}function o(t){var e;t.done?c(t.value):(e=t.value,e instanceof n?e:new n(function(t){t(e)})).then(a,u)}o((r=r.apply(t,e||[])).next())})};define(["require","exports","./AbstractAction"],function(t,e,n){"use strict";return class extends n.AbstractAction{execute(){return this.executeCallback()}executeCallback(){return __awaiter(this,void 0,void 0,function*(){return Promise.resolve(this.callback())})}}});