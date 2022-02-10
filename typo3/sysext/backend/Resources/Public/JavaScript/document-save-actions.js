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
import $ from"jquery";import Icons from"@typo3/backend/icons.js";class DocumentSaveActions{constructor(){this.preSubmitCallbacks=[],$(()=>{this.initializeSaveHandling()})}static getInstance(){return null===DocumentSaveActions.instance&&(DocumentSaveActions.instance=new DocumentSaveActions),DocumentSaveActions.instance}addPreSubmitCallback(t){if("function"!=typeof t)throw"callback must be a function.";this.preSubmitCallbacks.push(t)}initializeSaveHandling(){let t=!1;const e=["button[form]",'button[name^="_save"]','a[data-name^="_save"]','button[name="CMD"][value^="save"]','a[data-name="CMD"][data-value^="save"]'].join(",");$(".t3js-module-docheader").on("click",e,e=>{if(!t){t=!0;const a=$(e.currentTarget),n=a.attr("form")||a.attr("data-form")||null,r=n?$("#"+n):a.closest("form"),i=a.data("name")||e.currentTarget.getAttribute("name"),o=a.data("value")||e.currentTarget.getAttribute("value"),s=$("<input />").attr("type","hidden").attr("name",i).attr("value",o);for(let a of this.preSubmitCallbacks)if(a(e),e.isPropagationStopped())return t=!1,!1;r.append(s),r.on("submit",()=>{if(r.find(".has-error").length>0)return t=!1,!1;let e;const n=a.closest(".t3js-splitbutton");return n.length>0?(n.find("button").prop("disabled",!0),e=n.children().first()):(a.prop("disabled",!0),e=a),Icons.getIcon("spinner-circle-dark",Icons.sizes.small).then(t=>{e.find(".t3js-icon").replaceWith(t)}).catch(t=>{}),!0}),"A"!==e.currentTarget.tagName&&!a.attr("form")||e.isDefaultPrevented()||(r.find('[name="doSave"]').val("1"),r.trigger("submit"),e.preventDefault())}return!0})}}DocumentSaveActions.instance=null;export default DocumentSaveActions;