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
define(["require","exports","jquery","./Storage/Client"],function(e,i,t,s){"use strict";var r,c,n;!function(e){e.small="small",e.default="default",e.large="large",e.overlay="overlay"}(r||(r={})),function(e){e.default="default",e.disabled="disabled"}(c||(c={})),function(e){e.default="default",e.inline="inline"}(n||(n={}));class a{constructor(){this.sizes=r,this.states=c,this.markupIdentifiers=n,this.promiseCache={}}getIcon(e,i,a,o,h){const l=[e,i=i||r.default,a,o=o||c.default,h=h||n.default],u=l.join("_");return t.when(this.getIconRegistryCache()).pipe(e=>(s.isset("icon_registry_cache_identifier")&&s.get("icon_registry_cache_identifier")===e||(s.unsetByPrefix("icon_"),s.set("icon_registry_cache_identifier",e)),this.fetchFromLocal(u).then(null,()=>this.fetchFromRemote(l,u))))}getIconRegistryCache(){return this.isPromiseCached("icon_registry_cache_identifier")||this.putInPromiseCache("icon_registry_cache_identifier",t.ajax({url:TYPO3.settings.ajaxUrls.icons_cache,success:e=>e})),this.getFromPromiseCache("icon_registry_cache_identifier")}fetchFromRemote(e,i){return this.isPromiseCached(i)||this.putInPromiseCache(i,t.ajax({url:TYPO3.settings.ajaxUrls.icons,dataType:"html",data:{icon:JSON.stringify(e)},success:e=>(e.includes("t3js-icon")&&e.includes('<span class="icon-markup">')&&s.set("icon_"+i,e),e)})),this.getFromPromiseCache(i)}fetchFromLocal(e){const i=t.Deferred();return s.isset("icon_"+e)?i.resolve(s.get("icon_"+e)):i.reject(),i.promise()}isPromiseCached(e){return void 0!==this.promiseCache[e]}getFromPromiseCache(e){return this.promiseCache[e]}putInPromiseCache(e,i){this.promiseCache[e]=i}}let o;return o||(o=new a,TYPO3.Icons=o),o});