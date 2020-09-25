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
define(["require","exports","jquery","./Storage/Client"],(function(e,t,i,r){"use strict";var n,o,s;!function(e){e.small="small",e.default="default",e.large="large",e.overlay="overlay"}(n||(n={})),function(e){e.default="default",e.disabled="disabled"}(o||(o={})),function(e){e.default="default",e.inline="inline"}(s||(s={}));var c,a=function(){function e(){this.sizes=n,this.states=o,this.markupIdentifiers=s,this.promiseCache={}}return e.prototype.getIcon=function(e,t,c,a,u){var h=this,f=[e,t=t||n.default,c,a=a||o.default,u=u||s.default],l=f.join("_");return i.when(this.getIconRegistryCache()).pipe((function(e){return r.isset("icon_registry_cache_identifier")&&r.get("icon_registry_cache_identifier")===e||(r.unsetByPrefix("icon_"),r.set("icon_registry_cache_identifier",e)),h.fetchFromLocal(l).then(null,(function(){return h.fetchFromRemote(f,l)}))}))},e.prototype.getIconRegistryCache=function(){return this.isPromiseCached("icon_registry_cache_identifier")||this.putInPromiseCache("icon_registry_cache_identifier",i.ajax({url:TYPO3.settings.ajaxUrls.icons_cache,success:function(e){return e}})),this.getFromPromiseCache("icon_registry_cache_identifier")},e.prototype.fetchFromRemote=function(e,t){return this.isPromiseCached(t)||this.putInPromiseCache(t,i.ajax({url:TYPO3.settings.ajaxUrls.icons,dataType:"html",data:{icon:JSON.stringify(e)},success:function(e){return-1!==e.indexOf("t3js-icon")&&-1!==e.indexOf('<span class="icon-markup">')&&r.set("icon_"+t,e),e}})),this.getFromPromiseCache(t)},e.prototype.fetchFromLocal=function(e){var t=i.Deferred();return r.isset("icon_"+e)?t.resolve(r.get("icon_"+e)):t.reject(),t.promise()},e.prototype.isPromiseCached=function(e){return void 0!==this.promiseCache[e]},e.prototype.getFromPromiseCache=function(e){return this.promiseCache[e]},e.prototype.putInPromiseCache=function(e,t){this.promiseCache[e]=t},e}();return c||(c=new a,TYPO3.Icons=c),c}));