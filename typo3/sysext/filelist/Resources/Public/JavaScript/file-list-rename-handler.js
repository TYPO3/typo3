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
import g from"@typo3/core/event/regular-event.js";import{html as b}from"lit";import{FileListActionEvent as h}from"@typo3/filelist/file-list-actions.js";import m from"@typo3/backend/modal.js";import w from"@typo3/core/ajax/ajax-request.js";import u from"@typo3/backend/notification.js";import i from"@typo3/backend/viewport.js";import n from"~labels/core.core";import{topDocument as y}from"@typo3/core/utility/top-window.js";class C{constructor(){new g(h.rename,o=>{const a=o.detail.resources[0],d=m.advanced({title:n.get("file_rename.title"),type:m.types.default,size:m.sizes.small,content:this.composeEditForm(a),buttons:[{text:n.get("file_rename.button.cancel"),btnClass:"btn-default",name:"cancel",trigger:()=>{d.hideModal()}},{text:n.get("file_rename.button.rename"),btnClass:"btn-primary",name:"rename",form:"file-list-rename-form"}],callback:function(s){const f=s.querySelector("form");f?.addEventListener("submit",t=>{t.preventDefault();const l=new FormData(t.target).get("name");typeof l=="string"&&a.name!==l&&new w(TYPO3.settings.ajaxUrls.resource_rename).post({identifier:a.identifier,resourceName:l}).then(async p=>{const r=await p.resolve();if(r.status.length>0&&r.status.forEach(e=>{r.success?u.success(e.title,e.message):u.error(e.title,e.message)}),r.resource?.type==="folder"){const e=i.ContentContainer.getUrl();new URL(e,window.location.origin).searchParams.get("id")===r.origin.identifier?i.ContentContainer.setUrl(e+"&id="+r.resource.identifier):i.ContentContainer.refresh()}else i.ContentContainer.refresh();y.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh")),s.hideModal()})}),s.addEventListener("typo3-modal-shown",()=>{const t=f?.querySelector("input")??null;t!==null&&(t.focus(),t.setSelectionRange(0,a.name.lastIndexOf(".")))})}})}).bindTo(document)}composeEditForm(o){const c=o?.type==="folder"?n.get("folder_rename.label"):n.get("file_rename.label");return b`<form id=file-list-rename-form><label class=form-label for=rename_target>${c}</label> <input id=rename_target name=name class=form-control value=${o.name} required autofocus></form>`}}var v=new C;export{v as default};
