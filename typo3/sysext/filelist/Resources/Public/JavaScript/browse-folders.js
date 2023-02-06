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
import ElementBrowser from"@typo3/backend/element-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{FileListActionEvent,FileListActionSelector,FileListActionResourceFromElement}from"@typo3/filelist/file-list-actions.js";import InfoWindow from"@typo3/backend/info-window.js";class BrowseFolders{constructor(){this.importSelection=e=>{e.preventDefault();const t=e.detail.checkboxes;if(!t.length)return;const o=[];t.forEach((e=>{if(e.checked){const t=e.closest(FileListActionSelector.elementSelector),n=FileListActionResourceFromElement(t);"folder"===n.type&&n.identifier&&o.unshift(n)}})),o.length&&(o.forEach((function(e){BrowseFolders.insertElement(e.identifier)})),ElementBrowser.focusOpenerAndClose())},new RegularEvent(FileListActionEvent.primary,(e=>{e.preventDefault(),document.dispatchEvent(new CustomEvent(FileListActionEvent.select,{detail:{resource:e.detail.resource}}))})).bindTo(document),new RegularEvent(FileListActionEvent.select,(e=>{e.preventDefault();const t=e.detail.resource;"folder"===t.type&&BrowseFolders.insertElement(t.identifier,!0)})).bindTo(document),new RegularEvent(FileListActionEvent.show,(e=>{e.preventDefault();const t=e.detail.resource;InfoWindow.showItem("_"+t.type.toUpperCase(),t.identifier)})).bindTo(document),new RegularEvent("multiRecordSelection:action:import",this.importSelection).bindTo(document)}static insertElement(e,t){return ElementBrowser.insertElement("",e,e,e,t)}}export default new BrowseFolders;