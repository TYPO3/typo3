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
import{SeverityEnum as g}from"@typo3/backend/enum/severity.js";import f from"@typo3/backend/modal.js";import p from"@typo3/core/ajax/ajax-request.js";import d from"@typo3/core/event/regular-event.js";import u from"@typo3/backend/notification.js";import c from"@typo3/backend/viewport.js";import{FileListDragDropEvent as h}from"@typo3/filelist/file-list-dragdrop.js";var i;(function(l){l.move="move",l.copy="copy"})(i||(i={}));class v{constructor(){new d(h.transfer,o=>{const e=o.detail,r=e.target,a=e.resources;let n,t;if(e.resources.length===1){const m=e.resources[0];n=TYPO3.lang["message.transfer_resource.title"],t=TYPO3.lang["message.transfer_resource.text"].replace("%s",m.name).replace("%s",r.name)}else n=TYPO3.lang["message.transfer_resources.title"],t=TYPO3.lang["message.transfer_resources.text"].replace("%d",a.length.toString(10)).replace("%s",r.name);const s=f.confirm(n,t,g.notice,[{text:TYPO3.lang["message.button.cancel"],active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{s.hideModal()}},{text:TYPO3.lang["message.button.copy"],btnClass:"btn-primary",name:"copy",trigger:()=>{this.transfer(i.copy,a,r),s.hideModal()}},{text:TYPO3.lang["message.button.move"],btnClass:"btn-primary",name:"move",trigger:()=>{this.transfer(i.move,a,r),s.hideModal()}}])}).bindTo(top.document)}transfer(o,e,r){const a=[];e.forEach(t=>{const s={data:t.identifier,target:r.identifier};a.push(s)});const n={data:{[o]:a}};new p(top.TYPO3.settings.ajaxUrls.file_process).post(n).then(async t=>{const s=await t.resolve();this.handleMessages(s.messages??[]),c.ContentContainer.refresh(),top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh"))}).catch(async t=>{const s=await t.resolve();this.handleMessages(s.messages??[]),c.ContentContainer.refresh(),top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh"))})}handleMessages(o){o.forEach(e=>{u.showMessage(e.title||"",e.message||"",e.severity)})}}var y=new v;export{y as default};
