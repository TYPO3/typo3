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
import{ScaffoldIdentifierEnum as o}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{AbstractContainer as u}from"@typo3/backend/viewport/abstract-container.js";import m from"@typo3/backend/event/client-request.js";import c from"@typo3/backend/event/interaction-request.js";import l from"@typo3/backend/viewport/loader.js";import i from"@typo3/backend/event/trigger-request.js";class a extends u{get(){return document.querySelector(o.contentModuleIframe).contentWindow}beforeSetUrl(e){return this.consumerScope.invoke(new i("typo3.beforeSetUrl",e))}setUrl(e,t,r){const n=this.resolveRouterElement();if(n===null&&self!==top)return Promise.reject(new Error("Content container used in unsupported frame context"));t instanceof c||(t=new m("typo3.setUrl",null));const s=this.consumerScope.invoke(new i("typo3.setUrl",t));return s.then(()=>{n!==null?(l.start(),n.setAttribute("endpoint",e),n.setAttribute("module",r||null),n.parentElement.addEventListener("typo3-module-loaded",()=>l.finish(),{once:!0})):document.location.assign(e)}),s}getUrl(){return this.resolveRouterElement().getAttribute("endpoint")}refresh(e){const t=this.resolveIFrameElement();if(t===null)return Promise.reject();const r=this.consumerScope.invoke(new i("typo3.refresh",e));return r.then(()=>{t.contentWindow.location.reload()}),r}getIdFromUrl(){if(this.getUrl()){const e=new URL(this.getUrl(),window.location.origin).searchParams.get("id")??"";return parseInt(e,10)}return 0}resolveIFrameElement(){return document.querySelector(o.contentModuleIframe)}resolveRouterElement(){return document.querySelector(o.contentModuleRouter)}}export{a as default};
