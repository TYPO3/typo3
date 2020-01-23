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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Notification"],(function(require,exports,$,AjaxRequest,Notification){"use strict";Object.defineProperty(exports,"__esModule",{value:!0});class AjaxDispatcher{constructor(e){this.objectGroup=null,this.objectGroup=e}newRequest(e){return new AjaxRequest(e)}getEndpoint(e){if(void 0!==TYPO3.settings.ajaxUrls[e])return TYPO3.settings.ajaxUrls[e];throw'Undefined endpoint for route "'+e+'"'}send(e,t){const s=e.post(this.createRequestBody(t)).then(async e=>this.processResponse(await e.resolve()));return s.catch(e=>{Notification.error("Error "+e.message)}),s}createRequestBody(e){const t={};for(let s=0;s<e.length;s++)t["ajax["+s+"]"]=e[s];return t["ajax[context]"]=JSON.stringify(this.getContext()),t}getContext(){let e;return void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup]&&void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup].context&&(e=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),e}processResponse(json){if(json.hasErrors&&$.each(json.messages,(e,t)=>{Notification.error(t.title,t.message)}),json.stylesheetFiles&&$.each(json.stylesheetFiles,(e,t)=>{if(!t)return;const s=document.createElement("link");s.rel="stylesheet",s.type="text/css",s.href=t,document.querySelector("head").appendChild(s),delete json.stylesheetFiles[e]}),"object"==typeof json.inlineData&&(TYPO3.settings.FormEngineInline=$.extend(!0,TYPO3.settings.FormEngineInline,json.inlineData)),"object"==typeof json.requireJsModules)for(let e of json.requireJsModules)new Function(e)();return json.scriptCall&&json.scriptCall.length>0&&$.each(json.scriptCall,(index,value)=>{eval(value)}),json}}exports.AjaxDispatcher=AjaxDispatcher}));