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
import{ScaffoldIdentifierEnum as n}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{AbstractContainer as u}from"@typo3/backend/viewport/abstract-container.js";import m from"@typo3/backend/event/client-request.js";import c from"@typo3/backend/event/interaction-request.js";import l from"@typo3/backend/viewport/loader.js";import i from"@typo3/backend/event/trigger-request.js";class d extends u{get(){return document.querySelector(n.contentModuleIframe).contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new i("typo3.beforeSetUrl",e))}setUrl(e,t,r){const o=this.resolveRouterElement();if(o===null)return Promise.reject();t instanceof c||(t=new m("typo3.setUrl",null));const s=this.consumerScope.invoke(new i("typo3.setUrl",t));return s.then(()=>{l.start(),o.setAttribute("endpoint",e),o.setAttribute("module",r||null),o.parentElement.addEventListener("typo3-module-loaded",()=>l.finish(),{once:!0})}),s}getUrl(){return this.resolveRouterElement().getAttribute("endpoint")}refresh(e){const t=this.resolveIFrameElement();if(t===null)return Promise.reject();const r=this.consumerScope.invoke(new i("typo3.refresh",e));return r.then(()=>{t.contentWindow.location.reload()}),r}getIdFromUrl(){if(this.getUrl()){const e=new URL(this.getUrl(),window.location.origin).searchParams.get("id")??"";return parseInt(e,10)}return 0}resolveIFrameElement(){return document.querySelector(n.contentModuleIframe)}resolveRouterElement(){return document.querySelector(n.contentModuleRouter)}}export{d as default};
