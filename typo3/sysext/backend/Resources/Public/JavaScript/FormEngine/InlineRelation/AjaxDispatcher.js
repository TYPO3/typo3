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
define(["require","exports","./AjaxRequest","jquery","../../Notification"],function(require,exports,AjaxRequest_1,$,Notification){"use strict";Object.defineProperty(exports,"__esModule",{value:!0});class AjaxDispatcher{constructor(e){this.objectGroup=null,this.objectGroup=e}newRequest(e){return new AjaxRequest_1.AjaxRequest(e,this.objectGroup)}getEndpoint(e){if(void 0!==TYPO3.settings.ajaxUrls[e])return TYPO3.settings.ajaxUrls[e];throw'Undefined endpoint for route "'+e+'"'}send(e){const t=$.ajax(e.getEndpoint(),e.getOptions());return t.done(()=>{this.processResponse(t)}).fail(()=>{Notification.error("Error "+t.status,t.statusText)}),t}processResponse(xhr){const json=xhr.responseJSON;if(json.hasErrors&&$.each(json.messages,(e,t)=>{Notification.error(t.title,t.message)}),json.stylesheetFiles&&$.each(json.stylesheetFiles,(e,t)=>{if(!t)return;const s=document.createElement("link");s.rel="stylesheet",s.type="text/css",s.href=t,document.querySelector("head").appendChild(s),delete json.stylesheetFiles[e]}),"object"==typeof json.inlineData&&(TYPO3.settings.FormEngineInline=$.extend(!0,TYPO3.settings.FormEngineInline,json.inlineData)),"object"==typeof json.requireJsModules)for(let e of json.requireJsModules)new Function(e)();json.scriptCall&&json.scriptCall.length>0&&$.each(json.scriptCall,(index,value)=>{eval(value)})}}exports.AjaxDispatcher=AjaxDispatcher});