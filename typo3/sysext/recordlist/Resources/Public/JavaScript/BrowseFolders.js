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
import ElementBrowser from"TYPO3/CMS/Recordlist/ElementBrowser.js";import Modal from"TYPO3/CMS/Backend/Modal.js";import Severity from"TYPO3/CMS/Backend/Severity.js";import RegularEvent from"TYPO3/CMS/Core/Event/RegularEvent.js";class BrowseFolders{constructor(){new RegularEvent("click",(e,r)=>{e.preventDefault();const t=r.dataset.folderId;ElementBrowser.insertElement("",t,t,t,1===parseInt(r.dataset.close||"0",10))}).delegateTo(document,"[data-folder-id]"),new RegularEvent("click",(e,r)=>{e.preventDefault(),Modal.confirm("",r.dataset.message,Severity.error,[],[])}).delegateTo(document,".t3js-folderIdError")}}export default new BrowseFolders;