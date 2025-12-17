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
import d from"@typo3/core/document-service.js";import{MessageUtility as m}from"@typo3/backend/utility/message-utility.js";import"@typo3/backend/element/progress-bar-element.js";import c from"@typo3/core/ajax/ajax-request.js";import p,{Types as f}from"@typo3/backend/modal.js";import u from"@typo3/backend/notification.js";import b from"@typo3/backend/severity.js";import y from"@typo3/core/event/regular-event.js";import{topLevelModuleImport as h}from"@typo3/backend/utility/top-level-module-import.js";class g{constructor(){this.progressBar=null,d.ready().then(async()=>{await h("@typo3/backend/form-engine/element/online-media-form-element.js"),this.registerEvents()})}registerEvents(){new y("click",(e,o)=>{this.triggerModal(o)}).delegateTo(document,".t3js-online-media-add-btn")}addOnlineMedia(e,o,n){const r=e.dataset.targetFolder,t=e.dataset.onlineMediaAllowed,a=e.dataset.fileIrreObject;this.progressBar=document.createElement("typo3-backend-progress-bar"),document.body.appendChild(this.progressBar),this.progressBar.start(),new c(TYPO3.settings.ajaxUrls.online_media_create).post({url:n,targetFolder:r,allowed:t}).then(async i=>{const l=await i.resolve();if(l.file){const s={actionName:"typo3:foreignRelation:insert",objectGroup:a,table:"sys_file",uid:l.file};m.send(s),o.hideModal()}else u.error(top.TYPO3.lang["online_media.error.new_media.failed"],l.error);this.progressBar&&this.progressBar.done()})}triggerModal(e){const o=e.dataset.btnSubmit||"Add",n=e.dataset.placeholder||"Paste media url here...",r=e.dataset.onlineMediaAllowedHelpText||"Allow to embed from sources:",t=document.createElement("typo3-backend-formengine-online-media-form");t.placeholder=n,t.setAttribute("help-text",r),t.setAttribute("extensions",e.dataset.onlineMediaAllowed),p.advanced({type:f.default,title:e.title,content:t,severity:b.notice,callback:a=>{a.querySelector("typo3-backend-formengine-online-media-form").addEventListener("typo3:formengine:online-media-added",i=>{this.addOnlineMedia(e,a,i.detail["online-media-url"])})},buttons:[{text:o,btnClass:"btn btn-primary",name:"ok",trigger:()=>{t.querySelector("form").requestSubmit()}}]})}}var M=new g;export{M as default};
