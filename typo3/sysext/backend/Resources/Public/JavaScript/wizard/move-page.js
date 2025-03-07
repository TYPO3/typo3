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
import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";import Modal from"@typo3/backend/modal.js";import ModuleMenu from"@typo3/backend/module-menu.js";import Notification from"@typo3/backend/notification.js";import ImmediateAction from"@typo3/backend/action-button/immediate-action.js";import{lll}from"@typo3/core/lit-helper.js";export class MovePage{constructor(){this.initialize()}async initialize(){await DocumentService.ready(),this.registerEvents(document.querySelector(".element-browser-body"))}registerEvents(e){const t=document.querySelector("#elementRecordTitle").value,o=new URL(window.location.href);new RegularEvent("click",(async(e,a)=>{const i=document.querySelector("#makeCopy").checked,n=i?"copy":"move",c={cmd:{[o.searchParams.get("table")]:{[o.searchParams.get("uid")]:{[n]:a.dataset.position}}}};AjaxDataHandler.process(c).then((()=>{Modal.dismiss(),Notification.success(lll(i?"movePage.notification.pageCopied.title":"movePage.notification.pageMoved.title"),lll(i?"movePage.notification.pageCopied.message":"movePage.notification.pageMoved.message",t),10,[{label:lll("movePage.notification.pagePasted.action.dismiss")},{label:lll("movePage.notification.pagePasted.action.open",t),action:new ImmediateAction((()=>{ModuleMenu.App.showModule("web_list","id="+o.searchParams.get("uid"))}))}]),top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),ModuleMenu.App.showModule("web_list","id="+o.searchParams.get("expandPage"))}))})).delegateTo(e,'[data-action="paste"]')}}