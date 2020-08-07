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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","./AbstractContainer","jquery","../Event/ClientRequest","../Event/InteractionRequest","./Loader","../Utility","../Event/TriggerRequest"],(function(e,t,r,n,o,l,i,u,f,s){"use strict";o=__importDefault(o);class a extends n.AbstractContainer{get(){return o.default(r.ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new s("typo3.beforeSetUrl",e))}setUrl(e,t){let n;return null===this.resolveIFrameElement()?(n=o.default.Deferred(),n.reject(),n):(t instanceof i||(t=new l("typo3.setUrl",null)),n=this.consumerScope.invoke(new s("typo3.setUrl",t)),n.then(()=>{u.start(),o.default(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src",e).one("load",()=>{u.finish()})}),n)}getUrl(){return o.default(r.ScaffoldIdentifierEnum.contentModuleIframe).attr("src")}refresh(e){let t;const r=this.resolveIFrameElement();return null===r?(t=o.default.Deferred(),t.reject(),t):(t=this.consumerScope.invoke(new s("typo3.refresh",e)),t.then(()=>{r.contentWindow.location.reload()}),t)}getIdFromUrl(){return this.getUrl?parseInt(f.getParameterFromUrl(this.getUrl(),"id"),10):0}resolveIFrameElement(){const e=o.default(r.ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)}}return a}));