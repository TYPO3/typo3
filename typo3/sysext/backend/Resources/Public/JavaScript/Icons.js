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
define(["require","exports","jquery"],function(e,t,n){"use strict";var o,i,r,a,s,c;(i=o||(o={})).small="small",i.default="default",i.large="large",i.overlay="overlay",(a=r||(r={})).default="default",a.disabled="disabled",(c=s||(s={})).default="default",c.inline="inline";var u,d=function(){function e(){this.sizes=o,this.states=r,this.markupIdentifiers=s,this.cache={}}return e.prototype.getIcon=function(e,t,o,i,r){return n.when(this.fetch(e,t,o,i,r))},e.prototype.fetch=function(e,t,i,a,c){var u=[e,t=t||o.default,i,a=a||r.default,c=c||s.default],d=u.join("_");return this.isCached(d)||this.putInCache(d,n.ajax({url:TYPO3.settings.ajaxUrls.icons,dataType:"html",data:{icon:JSON.stringify(u)},success:function(e){return e}}).promise()),this.getFromCache(d).done()},e.prototype.isCached=function(e){return void 0!==this.cache[e]},e.prototype.getFromCache=function(e){return this.cache[e]},e.prototype.putInCache=function(e,t){this.cache[e]=t},e}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Icons&&(u=window.opener.TYPO3.Icons),parent&&parent.window.TYPO3&&parent.window.TYPO3.Icons&&(u=parent.window.TYPO3.Icons),top&&top.TYPO3.Icons&&(u=top.TYPO3.Icons)}catch(e){}return u||(u=new d,TYPO3.Icons=u),u});