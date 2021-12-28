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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","./AbstractContainer","jquery","../Event/ClientRequest","../Event/InteractionRequest","./Loader","../Utility","../Event/TriggerRequest"],(function(e,t,r,n,o,l,u,i,s,d){"use strict";o=__importDefault(o);class a extends n.AbstractContainer{get(){return(0,o.default)(r.ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new d("typo3.beforeSetUrl",e))}setUrl(e,t,r){let n;const s=this.resolveRouterElement();return null===s?(n=o.default.Deferred(),n.reject(),n):(t instanceof u||(t=new l("typo3.setUrl",null)),n=this.consumerScope.invoke(new d("typo3.setUrl",t)),n.then(()=>{i.start(),s.setAttribute("endpoint",e),s.setAttribute("module",r||null),s.parentElement.addEventListener("typo3-module-loaded",()=>i.finish(),{once:!0})}),n)}getUrl(){return this.resolveRouterElement().getAttribute("endpoint")}refresh(e){let t;const r=this.resolveIFrameElement();return null===r?(t=o.default.Deferred(),t.reject(),t):(t=this.consumerScope.invoke(new d("typo3.refresh",e)),t.then(()=>{r.contentWindow.location.reload()}),t)}getIdFromUrl(){return this.getUrl?parseInt(s.getParameterFromUrl(this.getUrl(),"id"),10):0}resolveIFrameElement(){const e=(0,o.default)(r.ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)}resolveRouterElement(){return document.querySelector(r.ScaffoldIdentifierEnum.contentModuleRouter)}}return a}));