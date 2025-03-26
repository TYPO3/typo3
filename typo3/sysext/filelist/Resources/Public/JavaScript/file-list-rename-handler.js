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
import g from"@typo3/core/event/regular-event.js";import{html as b}from"lit";import{FileListActionEvent as h}from"@typo3/filelist/file-list-actions.js";import s from"@typo3/backend/modal.js";import w from"@typo3/core/ajax/ajax-request.js";import f from"@typo3/backend/notification.js";import n from"@typo3/backend/viewport.js";class C{constructor(){new g(h.rename,o=>{const a=o.detail,u=a.resources[0],i=s.advanced({title:TYPO3.lang["file_rename.title"]||"Rename",type:s.types.default,size:s.sizes.small,content:this.composeEditForm(u),buttons:[{text:TYPO3.lang["file_rename.button.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel",trigger:()=>{i.hideModal()}},{text:TYPO3.lang["file_rename.button.rename"]||"Rename",btnClass:"btn-primary",name:"rename",trigger:()=>{i.querySelector("form")?.requestSubmit()}}],callback:function(r){const l=r.querySelector("form");l.addEventListener("submit",m=>{m.preventDefault();const d=new FormData(m.target),c=Object.fromEntries(d).name.toString();a.resources[0].name!==c&&new w(TYPO3.settings.ajaxUrls.resource_rename).post({identifier:a.resources[0].identifier,resourceName:c}).then(async p=>{const t=await p.resolve();if(t.status.length>0&&t.status.forEach(e=>{t.success?f.success(e.title,e.message):f.error(e.title,e.message)}),t.resource?.type==="folder"){const e=n.ContentContainer.getUrl();new URL(e,window.location.origin).searchParams.get("id")===t.origin.identifier?n.ContentContainer.setUrl(e+"&id="+t.resource.identifier):n.ContentContainer.refresh()}else n.ContentContainer.refresh();top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh")),r.hideModal()})}),r.addEventListener("typo3-modal-shown",()=>{l.querySelector("input")?.focus()})}})}).bindTo(document)}composeEditForm(o){return b`<form><label class=form-label for=rename_target>${TYPO3.lang["file_rename.label"]??"New filename"}</label> <input id=rename_target name=name class=form-control value=${o.name} required></form>`}}var v=new C;export{v as default};
