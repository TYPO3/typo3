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
import m from"@typo3/core/event/regular-event.js";import d from"@typo3/core/document-service.js";import p from"@typo3/backend/ajax-data-handler.js";import g from"@typo3/backend/modal.js";import i from"@typo3/backend/module-menu.js";import l from"@typo3/backend/notification.js";import u from"@typo3/backend/action-button/immediate-action.js";import{topDocument as f}from"@typo3/core/utility/top-window.js";import e from"~labels/backend.wizards.move_page";class v{constructor(){this.initialize()}async initialize(){await d.ready(),this.registerEvents(document.querySelector(".element-browser-body"))}registerEvents(r){const o=document.querySelector("#elementRecordTitle").value,t=new URL(window.location.href);new m("click",async(h,s)=>{const a=document.querySelector("#makeCopy").checked,n=a?"copy":"move",c={cmd:{[t.searchParams.get("table")]:{[t.searchParams.get("uid")]:{[n]:s.dataset.position}}}};p.process(c).then(()=>{g.dismiss(),l.success(a?e.get("movePage.notification.pageCopied.title"):e.get("movePage.notification.pageMoved.title"),a?e.get("movePage.notification.pageCopied.message",[o]):e.get("movePage.notification.pageMoved.message",[o]),10,[{label:e.get("movePage.notification.pagePasted.action.dismiss")},{label:e.get("movePage.notification.pagePasted.action.open",[o]),action:new u(()=>{i.App.showModule("records","id="+t.searchParams.get("uid"))})}]),f.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),i.App.showModule("records","id="+t.searchParams.get("expandPage"))})}).delegateTo(r,'[data-action="paste"]')}}export{v as MovePage};
