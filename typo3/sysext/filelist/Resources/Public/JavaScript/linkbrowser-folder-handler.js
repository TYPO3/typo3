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
import LinkBrowser from"@typo3/backend/link-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{FileListActionEvent}from"@typo3/filelist/file-list-actions.js";import InfoWindow from"@typo3/backend/info-window.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";class LinkBrowserFolderHandler{constructor(){new RegularEvent("click",((e,t)=>{e.preventDefault(),LinkBrowser.finalizeFunction(t.dataset.linkbrowserLink)})).delegateTo(document,"[data-linkbrowser-link]"),new RegularEvent(FileListActionEvent.primary,(e=>{e.preventDefault();const t=e.detail;t.action=FileListActionEvent.select,document.dispatchEvent(new CustomEvent(FileListActionEvent.select,{detail:t}))})).bindTo(document),new RegularEvent(FileListActionEvent.select,(e=>{e.preventDefault();const t=e.detail.resources[0];"folder"===t.type&&this.insertLink(t)})).bindTo(document),new RegularEvent(FileListActionEvent.show,(e=>{e.preventDefault();const t=e.detail.resources[0];InfoWindow.showItem("_"+t.type.toUpperCase(),t.identifier)})).bindTo(document)}insertLink(e){new AjaxRequest(TYPO3.settings.ajaxUrls.link_resource).post({identifier:e.identifier}).then((async e=>{const t=await e.resolve();t.status.forEach((e=>{Notification.showMessage(e.title,e.message,e.severity)})),t.success&&LinkBrowser.finalizeFunction(t.link)}))}}export default new LinkBrowserFolderHandler;