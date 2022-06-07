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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./Icons"],(function(t,e,a,n){"use strict";a=__importDefault(a);class r{constructor(){this.preSubmitCallbacks=[],(0,a.default)(()=>{this.initializeSaveHandling()})}static getInstance(){return null===r.instance&&(r.instance=new r),r.instance}addPreSubmitCallback(t){if("function"!=typeof t)throw"callback must be a function.";this.preSubmitCallbacks.push(t)}initializeSaveHandling(){let t=!1;const e=["button[form]",'button[name^="_save"]','a[data-name^="_save"]','button[name="CMD"][value^="save"]','a[data-name="CMD"][data-value^="save"]'].join(",");(0,a.default)(".t3js-module-docheader").on("click",e,e=>{if(!t){t=!0;const r=(0,a.default)(e.currentTarget),i=r.attr("form")||r.attr("data-form")||null,l=i?(0,a.default)("#"+i):r.closest("form"),u=r.data("name")||e.currentTarget.getAttribute("name"),s=r.data("value")||e.currentTarget.getAttribute("value"),o=(0,a.default)("<input />").attr("type","hidden").attr("name",u).attr("value",s);for(let a of this.preSubmitCallbacks)if(a(e),e.isPropagationStopped())return t=!1,!1;l.append(o),l.on("submit",()=>{if(l.find(".has-error").length>0)return t=!1,!1;let e;const a=r.closest(".t3js-splitbutton");return a.length>0?(a.find("button").prop("disabled",!0),e=a.children().first()):(r.prop("disabled",!0),e=r),n.getIcon("spinner-circle-dark",n.sizes.small).then(t=>{e.find(".t3js-icon").replaceWith(t)}).catch(t=>{}),!0}),"A"!==e.currentTarget.tagName&&!r.attr("form")||e.isDefaultPrevented()||(l.find('[name="doSave"]').val("1"),l.trigger("submit"),e.preventDefault())}return!0})}}return r.instance=null,r}));