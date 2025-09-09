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
import g from"@typo3/core/event/regular-event.js";import{html as b}from"lit";import{FileListActionEvent as h}from"@typo3/filelist/file-list-actions.js";import s from"@typo3/backend/modal.js";import w from"@typo3/core/ajax/ajax-request.js";import u from"@typo3/backend/notification.js";import i from"@typo3/backend/viewport.js";class C{constructor(){new g(h.rename,n=>{const a=n.detail.resources[0],m=s.advanced({title:TYPO3.lang["file_rename.title"]||"Rename",type:s.types.default,size:s.sizes.small,content:this.composeEditForm(a),buttons:[{text:TYPO3.lang["file_rename.button.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel",trigger:()=>{m.hideModal()}},{text:TYPO3.lang["file_rename.button.rename"]||"Rename",btnClass:"btn-primary",name:"rename",trigger:()=>{m.querySelector("form")?.requestSubmit()}}],callback:function(o){const c=o.querySelector("form");c.addEventListener("submit",t=>{t.preventDefault();const d=new FormData(t.target),f=Object.fromEntries(d).name.toString();a.name!==f&&new w(TYPO3.settings.ajaxUrls.resource_rename).post({identifier:a.identifier,resourceName:f}).then(async p=>{const r=await p.resolve();if(r.status.length>0&&r.status.forEach(e=>{r.success?u.success(e.title,e.message):u.error(e.title,e.message)}),r.resource?.type==="folder"){const e=i.ContentContainer.getUrl();new URL(e,window.location.origin).searchParams.get("id")===r.origin.identifier?i.ContentContainer.setUrl(e+"&id="+r.resource.identifier):i.ContentContainer.refresh()}else i.ContentContainer.refresh();top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh")),o.hideModal()})}),o.addEventListener("typo3-modal-shown",()=>{const t=c.querySelector("input");t!==null&&(t.focus(),t.setSelectionRange(0,a.name.lastIndexOf(".")))})}})}).bindTo(document)}composeEditForm(n){const l=n?.type==="folder"?TYPO3.lang["folder_rename.label"]??"New folder name":TYPO3.lang["file_rename.label"]??"New filename";return b`<form><label class=form-label for=rename_target>${l}</label> <input id=rename_target name=name class=form-control value=${n.name} required></form>`}}var y=new C;export{y as default};
