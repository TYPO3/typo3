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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","./AbstractContainer","jquery","../Event/ClientRequest","../Event/InteractionRequest","./Loader","../Utility","../Event/TriggerRequest"],(function(e,t,r,n,o,l,i,s,c,u){"use strict";class f extends n.AbstractContainer{get(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new u("typo3.beforeSetUrl",e))}setUrl(e,t){let n;return null===this.resolveIFrameElement()?(n=o.Deferred(),n.reject(),n):(t instanceof i||(t=new l("typo3.setUrl",null)),n=this.consumerScope.invoke(new u("typo3.setUrl",t)),n.then(()=>{s.start(),o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src",e).one("load",()=>{s.finish()})}),n)}getUrl(){return o(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src")}refresh(e){let t;const r=this.resolveIFrameElement();return null===r?(t=o.Deferred(),t.reject(),t):(t=this.consumerScope.invoke(new u("typo3.refresh",e)),t.then(()=>{r.contentWindow.location.reload()}),t)}getIdFromUrl(){return this.getUrl?parseInt(c.getParameterFromUrl(this.getUrl(),"id"),10):0}resolveIFrameElement(){const e=o(r.ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)}}return f}));