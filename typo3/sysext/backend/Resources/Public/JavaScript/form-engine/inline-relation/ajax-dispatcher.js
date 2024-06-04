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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{JavaScriptItemProcessor}from"@typo3/core/java-script-item-processor.js";import Notification from"@typo3/backend/notification.js";import Utility from"@typo3/backend/utility.js";export class AjaxDispatcher{constructor(t){this.objectGroup=null,this.objectGroup=t}newRequest(t){return new AjaxRequest(t)}getEndpoint(t){if(void 0!==TYPO3.settings.ajaxUrls[t])return TYPO3.settings.ajaxUrls[t];throw'Undefined endpoint for route "'+t+'"'}send(t,e){const s=t.post(this.createRequestBody(e)).then((async t=>this.processResponse(await t.resolve())));return s.catch((t=>{Notification.error("Error "+t.message)})),s}createRequestBody(t){const e={};for(let s=0;s<t.length;s++)e["ajax["+s+"]"]=t[s];return e["ajax[context]"]=JSON.stringify(this.getContext()),e}getContext(){let t;return void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup]&&void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup].context&&(t=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),t}processResponse(json){if(json.hasErrors)for(const t of json.messages)Notification.error(t.title,t.message);if(json.stylesheetFiles&&document.querySelector("head").append(...json.stylesheetFiles.filter((t=>t)).map((t=>{const e=document.createElement("link");return e.rel="stylesheet",e.type="text/css",e.href=t,e}))),"object"==typeof json.inlineData&&(TYPO3.settings.FormEngineInline=Utility.mergeDeep(TYPO3.settings.FormEngineInline,json.inlineData)),json.scriptItems instanceof Array&&json.scriptItems.length>0){const t=new JavaScriptItemProcessor;t.processItems(json.scriptItems)}if(json.scriptCall&&json.scriptCall.length>0)for(const scriptCall of json.scriptCall)eval(scriptCall);return json}}