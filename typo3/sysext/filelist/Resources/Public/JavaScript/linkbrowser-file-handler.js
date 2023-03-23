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
import LinkBrowser from"@typo3/backend/link-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{FileListActionEvent}from"@typo3/filelist/file-list-actions.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import InfoWindow from"@typo3/backend/info-window.js";import Notification from"@typo3/backend/notification.js";class LinkBrowserFileHandler{constructor(){new RegularEvent(FileListActionEvent.primary,(e=>{e.preventDefault();const t=e.detail;t.action=FileListActionEvent.select,document.dispatchEvent(new CustomEvent(FileListActionEvent.select,{detail:t}))})).bindTo(document),new RegularEvent(FileListActionEvent.select,(e=>{e.preventDefault();const t=e.detail.resources[0];"file"===t.type&&this.insertLink(t),"folder"===t.type&&this.loadContent(t)})).bindTo(document),new RegularEvent(FileListActionEvent.show,(e=>{e.preventDefault();const t=e.detail.resources[0];InfoWindow.showItem("_"+t.type.toUpperCase(),t.identifier)})).bindTo(document)}insertLink(e){new AjaxRequest(TYPO3.settings.ajaxUrls.link_resource).post({identifier:e.identifier}).then((async e=>{const t=await e.resolve();t.status.forEach((e=>{Notification.showMessage(e.title,e.message,e.severity)})),t.success&&LinkBrowser.finalizeFunction(t.link)}))}loadContent(e){if("folder"!==e.type)return;const t=document.location.href+"&contentOnly=1&expandFolder="+e.identifier;new AjaxRequest(t).get().then((e=>e.resolve())).then((e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e}))}}export default new LinkBrowserFileHandler;