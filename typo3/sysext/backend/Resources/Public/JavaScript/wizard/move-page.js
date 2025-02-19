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
import m from"@typo3/core/event/regular-event.js";import d from"@typo3/core/document-service.js";import p from"@typo3/backend/ajax-data-handler.js";import l from"@typo3/backend/modal.js";import i from"@typo3/backend/module-menu.js";import g from"@typo3/backend/notification.js";import u from"@typo3/backend/action-button/immediate-action.js";import{lll as t}from"@typo3/core/lit-helper.js";class f{constructor(){this.initialize()}async initialize(){await d.ready(),this.registerEvents(document.querySelector(".element-browser-body"))}registerEvents(n){const a=document.querySelector("#elementRecordTitle").value,e=new URL(window.location.href);new m("click",async(P,r)=>{const o=document.querySelector("#makeCopy").checked,s=o?"copy":"move",c={cmd:{[e.searchParams.get("table")]:{[e.searchParams.get("uid")]:{[s]:r.dataset.position}}}};p.process(c).then(()=>{l.dismiss(),g.success(t(o?"movePage.notification.pageCopied.title":"movePage.notification.pageMoved.title"),t(o?"movePage.notification.pageCopied.message":"movePage.notification.pageMoved.message",a),10,[{label:t("movePage.notification.pagePasted.action.dismiss")},{label:t("movePage.notification.pagePasted.action.open",a),action:new u(()=>{i.App.showModule("web_list","id="+e.searchParams.get("uid"))})}]),top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),i.App.showModule("web_list","id="+e.searchParams.get("expandPage"))})}).delegateTo(n,'[data-action="paste"]')}}export{f as MovePage};
