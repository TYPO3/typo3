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
define(["require","exports","jquery"],function(a,b,c){"use strict";var d;!function(a){a.small="small",a.default="default",a.large="large",a.overlay="overlay"}(d||(d={}));var e;!function(a){a.default="default",a.disabled="disabled"}(e||(e={}));var f;!function(a){a.default="default",a.inline="inline"}(f||(f={}));var g,h=function(){function a(){this.sizes=d,this.states=e,this.markupIdentifiers=f,this.cache={}}return a.prototype.getIcon=function(a,b,d,e,f){return c.when(this.fetch(a,b,d,e,f))},a.prototype.fetch=function(a,b,g,h,i){b=b||d.default,h=h||e.default,i=i||f.default;var j=[a,b,g,h,i],k=j.join("_");return this.isCached(k)||this.putInCache(k,c.ajax({url:TYPO3.settings.ajaxUrls.icons,dataType:"html",data:{icon:JSON.stringify(j)},success:function(a){return a}}).promise()),this.getFromCache(k).done()},a.prototype.isCached=function(a){return"undefined"!=typeof this.cache[a]},a.prototype.getFromCache=function(a){return this.cache[a]},a.prototype.putInCache=function(a,b){this.cache[a]=b},a}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Icons&&(g=window.opener.TYPO3.Icons),parent&&parent.window.TYPO3&&parent.window.TYPO3.Icons&&(g=parent.window.TYPO3.Icons),top&&top.TYPO3.Icons&&(g=top.TYPO3.Icons)}catch(a){}return g||(g=new h,TYPO3.Icons=g),g});