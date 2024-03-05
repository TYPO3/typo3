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
import{ScaffoldIdentifierEnum}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{AbstractContainer}from"@typo3/backend/viewport/abstract-container.js";import ClientRequest from"@typo3/backend/event/client-request.js";import InteractionRequest from"@typo3/backend/event/interaction-request.js";import Loader from"@typo3/backend/viewport/loader.js";import TriggerRequest from"@typo3/backend/event/trigger-request.js";class ContentContainer extends AbstractContainer{get(){return document.querySelector(ScaffoldIdentifierEnum.contentModuleIframe).contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new TriggerRequest("typo3.beforeSetUrl",e))}setUrl(e,t,r){const n=this.resolveRouterElement();if(null===n)return Promise.reject();t instanceof InteractionRequest||(t=new ClientRequest("typo3.setUrl",null));const o=this.consumerScope.invoke(new TriggerRequest("typo3.setUrl",t));return o.then((()=>{Loader.start(),n.setAttribute("endpoint",e),n.setAttribute("module",r||null),n.parentElement.addEventListener("typo3-module-loaded",(()=>Loader.finish()),{once:!0})})),o}getUrl(){return this.resolveRouterElement().getAttribute("endpoint")}refresh(e){const t=this.resolveIFrameElement();if(null===t)return Promise.reject();const r=this.consumerScope.invoke(new TriggerRequest("typo3.refresh",e));return r.then((()=>{t.contentWindow.location.reload()})),r}getIdFromUrl(){if(this.getUrl()){const e=new URL(this.getUrl(),window.location.origin).searchParams.get("id")??"";return parseInt(e,10)}return 0}resolveIFrameElement(){return document.querySelector(ScaffoldIdentifierEnum.contentModuleIframe)}resolveRouterElement(){return document.querySelector(ScaffoldIdentifierEnum.contentModuleRouter)}}export default ContentContainer;