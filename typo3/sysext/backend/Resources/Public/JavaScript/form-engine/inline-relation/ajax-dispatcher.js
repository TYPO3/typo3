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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{JavaScriptItemProcessor}from"@typo3/core/java-script-item-processor.js";import Notification from"@typo3/backend/notification.js";import Utility from"@typo3/backend/utility.js";export class AjaxDispatcher{constructor(e){this.objectGroup=null,this.objectGroup=e}newRequest(e){return new AjaxRequest(e)}getEndpoint(e){if(void 0!==TYPO3.settings.ajaxUrls[e])return TYPO3.settings.ajaxUrls[e];throw'Undefined endpoint for route "'+e+'"'}send(e,t){const s=e.post(this.createRequestBody(t)).then(async e=>this.processResponse(await e.resolve()));return s.catch(e=>{Notification.error("Error "+e.message)}),s}createRequestBody(e){const t={};for(let s=0;s<e.length;s++)t["ajax["+s+"]"]=e[s];return t["ajax[context]"]=JSON.stringify(this.getContext()),t}getContext(){let e;return void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup]&&void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup].context&&(e=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),e}processResponse(json){if(json.hasErrors)for(const e of json.messages)Notification.error(e.title,e.message);if(json.stylesheetFiles)for(const[e,t]of json.stylesheetFiles.entries()){if(!t)break;const s=document.createElement("link");s.rel="stylesheet",s.type="text/css",s.href=t,document.querySelector("head").appendChild(s),delete json.stylesheetFiles[e]}if("object"==typeof json.inlineData&&(TYPO3.settings.FormEngineInline=Utility.mergeDeep(TYPO3.settings.FormEngineInline,json.inlineData)),json.scriptItems instanceof Array&&json.scriptItems.length>0){const e=new JavaScriptItemProcessor;e.processItems(json.scriptItems)}if("object"==typeof json.requireJsModules)for(let e of json.requireJsModules)new Function(e)();if(json.scriptCall&&json.scriptCall.length>0)for(const scriptCall of json.scriptCall)eval(scriptCall);return json}}