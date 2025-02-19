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
import o from"@typo3/core/ajax/ajax-request.js";import{JavaScriptItemProcessor as i}from"@typo3/core/java-script-item-processor.js";import n from"@typo3/backend/notification.js";import c from"@typo3/backend/utility.js";class a{constructor(e){this.objectGroup=null,this.objectGroup=e}newRequest(e){return new o(e)}getEndpoint(e){if(typeof TYPO3.settings.ajaxUrls[e]<"u")return TYPO3.settings.ajaxUrls[e];throw'Undefined endpoint for route "'+e+'"'}send(e,t){const r=e.post(this.createRequestBody(t)).then(async s=>this.processResponse(await s.resolve()));return r.catch(s=>{n.error("Error "+s.message)}),r}createRequestBody(e){const t={};for(let r=0;r<e.length;r++)t["ajax["+r+"]"]=e[r];return t["ajax[context]"]=JSON.stringify(this.getContext()),t}getContext(){let e;return typeof TYPO3.settings.FormEngineInline.config[this.objectGroup]<"u"&&typeof TYPO3.settings.FormEngineInline.config[this.objectGroup].context<"u"&&(e=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),e}processResponse(e){if(e.hasErrors)for(const t of e.messages)n.error(t.title,t.message);return e.stylesheetFiles&&document.querySelector("head").append(...e.stylesheetFiles.filter(t=>t).map(t=>{const r=document.createElement("link");return r.rel="stylesheet",r.type="text/css",r.href=t,r})),typeof e.inlineData=="object"&&(TYPO3.settings.FormEngineInline=c.mergeDeep(TYPO3.settings.FormEngineInline,e.inlineData)),e.scriptItems instanceof Array&&e.scriptItems.length>0&&new i().processItems(e.scriptItems),e}}export{a as AjaxDispatcher};
