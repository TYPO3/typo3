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
import m from"@typo3/core/document-service.js";import{MessageUtility as c}from"@typo3/backend/utility/message-utility.js";import d from"nprogress";import p from"@typo3/core/ajax/ajax-request.js";import f,{Types as u}from"@typo3/backend/modal.js";import b from"@typo3/backend/notification.js";import y from"@typo3/backend/severity.js";import M from"@typo3/core/event/regular-event.js";import{topLevelModuleImport as w}from"@typo3/backend/utility/top-level-module-import.js";class h{constructor(){m.ready().then(async()=>{await w("@typo3/backend/form-engine/element/online-media-form-element.js"),this.registerEvents()})}registerEvents(){new M("click",(e,o)=>{this.triggerModal(o)}).delegateTo(document,".t3js-online-media-add-btn")}addOnlineMedia(e,o,a){const i=e.dataset.targetFolder,t=e.dataset.onlineMediaAllowed,n=e.dataset.fileIrreObject;d.start(),new p(TYPO3.settings.ajaxUrls.online_media_create).post({url:a,targetFolder:i,allowed:t}).then(async r=>{const l=await r.resolve();if(l.file){const s={actionName:"typo3:foreignRelation:insert",objectGroup:n,table:"sys_file",uid:l.file};c.send(s),o.hideModal()}else b.error(top.TYPO3.lang["online_media.error.new_media.failed"],l.error);d.done()})}triggerModal(e){const o=e.dataset.btnSubmit||"Add",a=e.dataset.placeholder||"Paste media url here...",i=e.dataset.onlineMediaAllowedHelpText||"Allow to embed from sources:",t=document.createElement("typo3-backend-formengine-online-media-form");t.placeholder=a,t.setAttribute("help-text",i),t.setAttribute("extensions",e.dataset.onlineMediaAllowed),f.advanced({type:u.default,title:e.title,content:t,severity:y.notice,callback:n=>{n.querySelector("typo3-backend-formengine-online-media-form").addEventListener("typo3:formengine:online-media-added",r=>{this.addOnlineMedia(e,n,r.detail["online-media-url"])})},buttons:[{text:o,btnClass:"btn btn-primary",name:"ok",trigger:()=>{t.querySelector("form").requestSubmit()}}]})}}var v=new h;export{v as default};
