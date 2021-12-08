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
import{ScaffoldIdentifierEnum}from"TYPO3/CMS/Backend/Enum/Viewport/ScaffoldIdentifier.js";import{AbstractContainer}from"TYPO3/CMS/Backend/Viewport/AbstractContainer.js";import $ from"jquery";import ClientRequest from"TYPO3/CMS/Backend/Event/ClientRequest.js";import InteractionRequest from"TYPO3/CMS/Backend/Event/InteractionRequest.js";import Loader from"TYPO3/CMS/Backend/Viewport/Loader.js";import Utility from"TYPO3/CMS/Backend/Utility.js";import TriggerRequest from"TYPO3/CMS/Backend/Event/TriggerRequest.js";class ContentContainer extends AbstractContainer{get(){return $(ScaffoldIdentifierEnum.contentModuleIframe)[0].contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new TriggerRequest("typo3.beforeSetUrl",e))}setUrl(e,t,r){let n;const o=this.resolveRouterElement();return null===o?(n=$.Deferred(),n.reject(),n):(t instanceof InteractionRequest||(t=new ClientRequest("typo3.setUrl",null)),n=this.consumerScope.invoke(new TriggerRequest("typo3.setUrl",t)),n.then(()=>{Loader.start(),o.setAttribute("endpoint",e),o.setAttribute("module",r||null),o.parentElement.addEventListener("typo3-module-loaded",()=>Loader.finish(),{once:!0})}),n)}getUrl(){return this.resolveRouterElement().getAttribute("endpoint")}refresh(e){let t;const r=this.resolveIFrameElement();return null===r?(t=$.Deferred(),t.reject(),t):(t=this.consumerScope.invoke(new TriggerRequest("typo3.refresh",e)),t.then(()=>{r.contentWindow.location.reload()}),t)}getIdFromUrl(){return this.getUrl?parseInt(Utility.getParameterFromUrl(this.getUrl(),"id"),10):0}resolveIFrameElement(){const e=$(ScaffoldIdentifierEnum.contentModuleIframe+":first");return 0===e.length?null:e.get(0)}resolveRouterElement(){return document.querySelector(ScaffoldIdentifierEnum.contentModuleRouter)}}export default ContentContainer;