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
import RegularEvent from"@typo3/core/event/regular-event.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{FileListActionEvent}from"@typo3/filelist/file-list-actions.js";import InfoWindow from"@typo3/backend/info-window.js";class CreateFolder{constructor(){new RegularEvent(FileListActionEvent.primary,(e=>{e.preventDefault();const t=e.detail;t.action=FileListActionEvent.select,document.dispatchEvent(new CustomEvent(FileListActionEvent.select,{detail:t}))})).bindTo(document),new RegularEvent(FileListActionEvent.select,(e=>{e.preventDefault();const t=e.detail.resources[0];"folder"===t.type&&this.loadContent(t)})).bindTo(document),new RegularEvent(FileListActionEvent.show,(e=>{e.preventDefault();const t=e.detail.resources[0];InfoWindow.showItem("_"+t.type.toUpperCase(),t.identifier)})).bindTo(document)}loadContent(e){if("folder"!==e.type)return;const t=document.location.href+"&contentOnly=1&expandFolder="+e.identifier;new AjaxRequest(t).get().then((e=>e.resolve())).then((e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e}))}}export default new CreateFolder;