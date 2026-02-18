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
import{SeverityEnum as g}from"@typo3/backend/enum/severity.js";import p from"@typo3/backend/modal.js";import d from"@typo3/core/ajax/ajax-request.js";import u from"@typo3/core/event/regular-event.js";import h from"@typo3/backend/notification.js";import m from"@typo3/backend/viewport.js";import s from"~labels/filelist.transfer_handler";import{FileListDragDropEvent as v}from"@typo3/filelist/file-list-dragdrop.js";var c;(function(l){l.move="move",l.copy="copy"})(c||(c={}));class y{constructor(){new u(v.transfer,n=>{const e=n.detail,a=e.target,o=e.resources;let i,t;if(e.resources.length===1){const f=e.resources[0];i=s.get("message.transfer_resource.title"),t=s.get("message.transfer_resource.text").replace("%s",f.name).replace("%s",a.name)}else i=s.get("message.transfer_resources.title"),t=s.get("message.transfer_resources.text").replace("%d",o.length.toString(10)).replace("%s",a.name);const r=p.confirm(i,t,g.notice,[{text:s.get("message.button.cancel"),active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{r.hideModal()}},{text:s.get("message.button.copy"),btnClass:"btn-primary",name:"copy",trigger:()=>{this.transfer(c.copy,o,a),r.hideModal()}},{text:s.get("message.button.move"),btnClass:"btn-primary",name:"move",trigger:()=>{this.transfer(c.move,o,a),r.hideModal()}}])}).bindTo(top.document)}transfer(n,e,a){const o=[];e.forEach(t=>{const r={data:t.identifier,target:a.identifier};o.push(r)});const i={data:{[n]:o}};new d(top.TYPO3.settings.ajaxUrls.file_process).post(i).then(async t=>{const r=await t.resolve();this.handleMessages(r.messages??[]),m.ContentContainer.refresh(),top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh"))}).catch(async t=>{const r=await t.resolve();this.handleMessages(r.messages??[]),m.ContentContainer.refresh(),top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh"))})}handleMessages(n){n.forEach(e=>{h.showMessage(e.title||"",e.message||"",e.severity)})}}var b=new y;export{b as default};
