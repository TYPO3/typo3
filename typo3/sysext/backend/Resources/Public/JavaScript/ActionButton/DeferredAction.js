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
var __awaiter=this&&this.__awaiter||function(t,e,n,i){return new(n||(n=Promise))(function(c,r){function a(t){try{o(i.next(t))}catch(t){r(t)}}function u(t){try{o(i.throw(t))}catch(t){r(t)}}function o(t){t.done?c(t.value):new n(function(e){e(t.value)}).then(a,u)}o((i=i.apply(t,e||[])).next())})};define(["require","exports","./AbstractAction","../Icons"],function(t,e,n,i){"use strict";return class extends n.AbstractAction{execute(t){return __awaiter(this,void 0,void 0,function*(){return i.getIcon("spinner-circle-light",i.sizes.small).then(e=>{t.innerHTML=e}),yield this.executeCallback()})}executeCallback(){return __awaiter(this,void 0,void 0,function*(){return yield this.callback()})}}});