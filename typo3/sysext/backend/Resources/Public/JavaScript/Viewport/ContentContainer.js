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
var __extends=this&&this.__extends||function(){var e=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var r in t)t.hasOwnProperty(r)&&(e[r]=t[r])};return function(t,r){function n(){this.constructor=t}e(t,r),t.prototype=null===r?Object.create(r):(n.prototype=r.prototype,new n)}}();define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","./AbstractContainer","jquery","./Loader","../Utility","../Event/TriggerRequest"],function(e,t,r,n,o,i,u,c){"use strict";return function(e){function t(){return null!==e&&e.apply(this,arguments)||this}return __extends(t,e),t.prototype.get=function(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow},t.prototype.beforeSetUrl=function(e){return this.consumerScope.invoke(new c("typo3.beforeSetUrl",e))},t.prototype.setUrl=function(e,t){var n;return null===this.resolveIFrameElement()?((n=o.Deferred()).reject(),n):((n=this.consumerScope.invoke(new c("typo3.setUrl",t))).then(function(){i.start(),o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src",e).one("load",function(){i.finish()})}),n)},t.prototype.getUrl=function(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src")},t.prototype.refresh=function(e,t){var r,n=this.resolveIFrameElement();return null===n?((r=o.Deferred()).reject(),r):((r=this.consumerScope.invoke(new c("typo3.refresh",t))).then(function(){n.contentWindow.location.reload(e)}),r)},t.prototype.getIdFromUrl=function(){return this.getUrl?parseInt(u.getParameterFromUrl(this.getUrl(),"id"),10):0},t.prototype.resolveIFrameElement=function(){var e=o(r.ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)},t}(n.AbstractContainer)});