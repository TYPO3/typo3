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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ClientStorage from"@typo3/backend/storage/client.js";import{Sizes,States,MarkupIdentifiers}from"@typo3/backend/enum/icon-types.js";class Icons{constructor(){this.sizes=Sizes,this.states=States,this.markupIdentifiers=MarkupIdentifiers,this.promiseCache={}}getIcon(e,t,i,s,r){const o=[e,t=t||Sizes.default,i,s=s||States.default,r=r||MarkupIdentifiers.default],n=o.join("_");return this.getIconRegistryCache().then(e=>(ClientStorage.isset("icon_registry_cache_identifier")&&ClientStorage.get("icon_registry_cache_identifier")===e||(ClientStorage.unsetByPrefix("icon_"),ClientStorage.set("icon_registry_cache_identifier",e)),this.fetchFromLocal(n).then(null,()=>this.fetchFromRemote(o,n))))}getIconRegistryCache(){const e="icon_registry_cache_identifier";return this.isPromiseCached(e)||this.putInPromiseCache(e,new AjaxRequest(TYPO3.settings.ajaxUrls.icons_cache).get().then(async e=>await e.resolve())),this.getFromPromiseCache(e)}fetchFromRemote(e,t){if(!this.isPromiseCached(t)){const i={icon:JSON.stringify(e)};this.putInPromiseCache(t,new AjaxRequest(TYPO3.settings.ajaxUrls.icons).withQueryArguments(i).get().then(async e=>{const i=await e.resolve();return i.includes("t3js-icon")&&i.includes('<span class="icon-markup">')&&ClientStorage.set("icon_"+t,i),i}))}return this.getFromPromiseCache(t)}fetchFromLocal(e){return ClientStorage.isset("icon_"+e)?Promise.resolve(ClientStorage.get("icon_"+e)):Promise.reject()}isPromiseCached(e){return void 0!==this.promiseCache[e]}getFromPromiseCache(e){return this.promiseCache[e]}putInPromiseCache(e,t){this.promiseCache[e]=t}}let iconsObject;iconsObject||(iconsObject=new Icons,TYPO3.Icons=iconsObject);export default iconsObject;