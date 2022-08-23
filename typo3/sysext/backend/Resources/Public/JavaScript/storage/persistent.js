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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";class Persistent{constructor(){this.data=null}get(e){if(null===this.data){const s=this.loadFromServer();return this.getRecursiveDataByDeepKey(s,e.split("."))}return this.getRecursiveDataByDeepKey(this.data,e.split("."))}set(e,s){return null!==this.data&&(this.data=this.setRecursiveDataByDeepKey(this.data,e.split("."),s)),this.storeOnServer(e,s)}async addToList(e,s){const t=await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"addToList",key:e,value:s});return this.resolveResponse(t)}async removeFromList(e,s){const t=await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"removeFromList",key:e,value:s});return this.resolveResponse(t)}async unset(e){const s=await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"unset",key:e});return this.resolveResponse(s)}clear(){new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"clear"}),this.data=null}isset(e){const s=this.get(e);return null!=s}load(e){this.data=e}loadFromServer(){const e=new URL(location.origin+TYPO3.settings.ajaxUrls.usersettings_process);e.searchParams.set("action","getAll");const s=new XMLHttpRequest;if(s.open("GET",e.toString(),!1),s.send(),200===s.status){const e=JSON.parse(s.responseText);return this.data=e,e}throw`Unexpected response code ${s.status}, reason: ${s.responseText}`}async storeOnServer(e,s){const t=await new AjaxRequest(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"set",key:e,value:s});return this.resolveResponse(t)}getRecursiveDataByDeepKey(e,s){if(1===s.length)return(e||{})[s[0]];const t=s.shift();return this.getRecursiveDataByDeepKey(e[t]||{},s)}setRecursiveDataByDeepKey(e,s,t){if(1===s.length)(e=e||{})[s[0]]=t;else{const a=s.shift();e[a]=this.setRecursiveDataByDeepKey(e[a]||{},s,t)}return e}async resolveResponse(e){const s=await e.resolve();return this.data=s,s}}export default new Persistent;