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
import m from"@typo3/core/event/regular-event.js";import d from"@typo3/core/document-service.js";import p from"@typo3/backend/ajax-data-handler.js";import l from"@typo3/backend/modal.js";import i from"@typo3/backend/module-menu.js";import g from"@typo3/backend/notification.js";import u from"@typo3/backend/action-button/immediate-action.js";import{lll as o}from"@typo3/core/lit-helper.js";class f{constructor(){this.initialize()}async initialize(){await d.ready(),this.registerEvents(document.querySelector(".element-browser-body"))}registerEvents(r){const a=document.querySelector("#elementRecordTitle").value,e=new URL(window.location.href);new m("click",async(P,n)=>{const t=document.querySelector("#makeCopy").checked,s=t?"copy":"move",c={cmd:{[e.searchParams.get("table")]:{[e.searchParams.get("uid")]:{[s]:n.dataset.position}}}};p.process(c).then(()=>{l.dismiss(),g.success(o(t?"movePage.notification.pageCopied.title":"movePage.notification.pageMoved.title"),o(t?"movePage.notification.pageCopied.message":"movePage.notification.pageMoved.message",a),10,[{label:o("movePage.notification.pagePasted.action.dismiss")},{label:o("movePage.notification.pagePasted.action.open",a),action:new u(()=>{i.App.showModule("records","id="+e.searchParams.get("uid"))})}]),top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),i.App.showModule("records","id="+e.searchParams.get("expandPage"))})}).delegateTo(r,'[data-action="paste"]')}}export{f as MovePage};
