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
define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","./Storage/Client"],(function(e,i,t,s){"use strict";var r,n,c;!function(e){e.small="small",e.default="default",e.large="large",e.overlay="overlay"}(r||(r={})),function(e){e.default="default",e.disabled="disabled"}(n||(n={})),function(e){e.default="default",e.inline="inline"}(c||(c={}));class o{constructor(){this.sizes=r,this.states=n,this.markupIdentifiers=c,this.promiseCache={}}getIcon(e,i,t,o,a){const h=[e,i=i||r.default,t,o=o||n.default,a=a||c.default],l=h.join("_");return this.getIconRegistryCache().then(e=>(s.isset("icon_registry_cache_identifier")&&s.get("icon_registry_cache_identifier")===e||(s.unsetByPrefix("icon_"),s.set("icon_registry_cache_identifier",e)),this.fetchFromLocal(l).then(null,()=>this.fetchFromRemote(h,l))))}getIconRegistryCache(){return this.isPromiseCached("icon_registry_cache_identifier")||this.putInPromiseCache("icon_registry_cache_identifier",new t(TYPO3.settings.ajaxUrls.icons_cache).get().then(async e=>await e.resolve())),this.getFromPromiseCache("icon_registry_cache_identifier")}fetchFromRemote(e,i){if(!this.isPromiseCached(i)){const r={icon:JSON.stringify(e)};this.putInPromiseCache(i,new t(TYPO3.settings.ajaxUrls.icons).withQueryArguments(r).get().then(async e=>{const t=await e.resolve();return t.includes("t3js-icon")&&t.includes('<span class="icon-markup">')&&s.set("icon_"+i,t),t}))}return this.getFromPromiseCache(i)}fetchFromLocal(e){return s.isset("icon_"+e)?Promise.resolve(s.get("icon_"+e)):Promise.reject()}isPromiseCached(e){return void 0!==this.promiseCache[e]}getFromPromiseCache(e){return this.promiseCache[e]}putInPromiseCache(e,i){this.promiseCache[e]=i}}let a;return a||(a=new o,TYPO3.Icons=a),a}));