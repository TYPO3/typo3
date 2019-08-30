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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","./AbstractContainer","jquery","./Loader","../Utility","../Event/TriggerRequest"],function(e,t,r,n,o,l,i,s){"use strict";return class extends n.AbstractContainer{get(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new s("typo3.beforeSetUrl",e))}setUrl(e,t){let n;return null===this.resolveIFrameElement()?((n=o.Deferred()).reject(),n):((n=this.consumerScope.invoke(new s("typo3.setUrl",t))).then(()=>{l.start(),o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src",e).one("load",()=>{l.finish()})}),n)}getUrl(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src")}refresh(e,t){let r;const n=this.resolveIFrameElement();return null===n?((r=o.Deferred()).reject(),r):((r=this.consumerScope.invoke(new s("typo3.refresh",t))).then(()=>{n.contentWindow.location.reload(e)}),r)}getIdFromUrl(){return this.getUrl?parseInt(i.getParameterFromUrl(this.getUrl(),"id"),10):0}resolveIFrameElement(){const e=o(r.ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)}}});