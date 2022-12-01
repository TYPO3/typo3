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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{default as Modal}from"@typo3/backend/modal.js";export default class CreateFolder{constructor(){DocumentService.ready().then((()=>{new RegularEvent("click",((e,t)=>{e.preventDefault();const o=new URL(t.href,window.location.origin);o.searchParams.set("contentOnly","1"),new AjaxRequest(o.toString()).get().then((e=>e.resolve())).then((e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e}))})).delegateTo(document,'[data-filelist-action="list-folders"]'),new RegularEvent("click",((e,t)=>{e.preventDefault(),top.list_frame.document.location.href=t.href,Modal.currentModal.addEventListener("typo3-modal-hide",(e=>{e.stopImmediatePropagation()})),Modal.dismiss()})).delegateTo(document,'[data-filelist-action="open-module"]')}))}}