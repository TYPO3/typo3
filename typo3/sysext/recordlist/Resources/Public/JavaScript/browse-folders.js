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
import ElementBrowser from"@typo3/recordlist/element-browser.js";import Modal from"@typo3/backend/modal.js";import Severity from"@typo3/backend/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";class BrowseFolders{constructor(){new RegularEvent("click",(e,r)=>{e.preventDefault();const t=r.dataset.folderId;ElementBrowser.insertElement("",t,t,t,1===parseInt(r.dataset.close||"0",10))}).delegateTo(document,"[data-folder-id]"),new RegularEvent("click",(e,r)=>{e.preventDefault(),Modal.confirm("",r.dataset.message,Severity.error,[],[])}).delegateTo(document,".t3js-folderIdError")}}export default new BrowseFolders;