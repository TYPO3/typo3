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
import p from"@typo3/core/event/regular-event.js";import v from"@typo3/core/document-service.js";import y from"@typo3/backend/ajax-data-handler.js";import h from"@typo3/backend/modal.js";import w from"@typo3/backend/notification.js";import C from"@typo3/backend/action-button/immediate-action.js";import{lll as e}from"@typo3/core/lit-helper.js";import f from"@typo3/backend/viewport.js";class S{constructor(){this.initialize()}async initialize(){await v.ready(),this.registerEvents(document.querySelector(".element-browser-body"))}registerEvents(s){new p("change",async i=>{const t=document.querySelector("#elementRecordTitle").value,r=new URL(window.location.href).searchParams.get("uid"),o=document.querySelector("h2");o&&(o.innerText=e("headline."+(i.target.checked?"copy":"move"),t,r));const a=i.target.checked?e("copyElementToHere"):e("moveElementToHere");document.querySelectorAll('[data-action="paste"]').forEach(n=>{n.querySelector("span.t3js-button-label").textContent=a})}).delegateTo(s,"#makeCopy"),new p("click",async(i,t)=>{const m=document.querySelector("#makeCopy"),r=document.querySelector("#elementRecordTitle").value,o=document.querySelector("#pageRecordTitle").value,a=document.querySelector("#pageUid").value,n=new URL(window.location.href),d=n.searchParams.get("uid"),c=new URL(n.searchParams.get("returnUrl"),window.origin),l=m.checked,g=l?"copy":"move",u={cmd:{tt_content:{[d]:{[g]:t.dataset.position}}}};t.dataset.colpos!==void 0&&(u.data={tt_content:{[d]:{colPos:t.dataset.colpos}}}),y.process(u).then(()=>{h.dismiss(),w.success(e(l?"moveElement.notification.elementCopied.title":"moveElement.notification.elementMoved.title"),e(l?"moveElement.notification.elementCopied.message":"moveElement.notification.elementMoved.message",r),10,[{label:e("moveElement.notification.elementPasted.action.dismiss")},{label:e("moveElement.notification.elementPasted.action.open",o),action:new C(()=>{c.searchParams.set("id",a),f.ContentContainer.setUrl(c.toString())})}]),f.ContentContainer.setUrl(c.toString())})}).delegateTo(s,'[data-action="paste"]')}}export{S as MoveContentElement};
