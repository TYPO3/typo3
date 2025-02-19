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
import r from"@typo3/core/ajax/ajax-request.js";class o{constructor(){this.data=null}get(s){return this.data===null&&(this.data=this.loadFromServer()),this.getRecursiveDataByDeepKey(this.data,s.split("."))}set(s,e){return this.data!==null&&(this.data=this.setRecursiveDataByDeepKey(this.data,s.split("."),e)),this.storeOnServer(s,e)}async addToList(s,e){const t=await new r(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"addToList",key:s,value:e});return this.resolveResponse(t)}async removeFromList(s,e){const t=await new r(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"removeFromList",key:s,value:e});return this.resolveResponse(t)}async unset(s){const e=await new r(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"unset",key:s});return this.resolveResponse(e)}clear(){new r(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"clear"}),this.data=null}isset(s){const e=this.get(s);return typeof e<"u"&&e!==null}load(s){this.data=s}loadFromServer(){const s=new URL(location.origin+TYPO3.settings.ajaxUrls.usersettings_process);s.searchParams.set("action","getAll");const e=new XMLHttpRequest;if(e.open("GET",s.toString(),!1),e.send(),e.status===200)return JSON.parse(e.responseText);throw`Unexpected response code ${e.status}, reason: ${e.responseText}`}async storeOnServer(s,e){const t=await new r(TYPO3.settings.ajaxUrls.usersettings_process).post({action:"set",key:s,value:e});return this.resolveResponse(t)}getRecursiveDataByDeepKey(s,e){if(e.length===1)return(s||{})[e[0]];const t=e.shift();return this.getRecursiveDataByDeepKey(s[t]||{},e)}setRecursiveDataByDeepKey(s,e,t){if(e.length===1)s=s||{},s[e[0]]=t;else{const n=e.shift();s[n]=this.setRecursiveDataByDeepKey(s[n]||{},e,t)}return s}async resolveResponse(s){const e=await s.resolve();return this.data=e,e}}var i=new o;export{i as default};
