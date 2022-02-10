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
import{MessageUtility}from"@typo3/backend/utility/message-utility.js";import ElementBrowser from"@typo3/recordlist/element-browser.js";import NProgress from"nprogress";import RegularEvent from"@typo3/core/event/regular-event.js";var Icons=TYPO3.Icons;class BrowseFiles{constructor(){this.importSelection=e=>{e.preventDefault();const t=e.target,n=e.detail.checkboxes;if(!n.length)return;const s=[];n.forEach(e=>{e.checked&&e.name&&e.dataset.fileName&&e.dataset.fileUid&&s.unshift({uid:e.dataset.fileUid,fileName:e.dataset.fileName})}),Icons.getIcon("spinner-circle",Icons.sizes.small,null,null,Icons.markupIdentifiers.inline).then(e=>{t.classList.add("disabled"),t.innerHTML=e}),NProgress.configure({parent:".element-browser-main-content",showSpinner:!1}),NProgress.start();const i=1/s.length;BrowseFiles.handleNext(s),new RegularEvent("message",e=>{if(!MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;"typo3:foreignRelation:inserted"===e.data.actionName&&(s.length>0?(NProgress.inc(i),BrowseFiles.handleNext(s)):(NProgress.done(),ElementBrowser.focusOpenerAndClose()))}).bindTo(window)},new RegularEvent("click",(e,t)=>{e.preventDefault(),BrowseFiles.insertElement(t.dataset.fileName,Number(t.dataset.fileUid),1===parseInt(t.dataset.close||"0",10))}).delegateTo(document,"[data-close]"),new RegularEvent("multiRecordSelection:action:import",this.importSelection).bindTo(document)}static insertElement(e,t,n){return ElementBrowser.insertElement("sys_file",String(t),e,String(t),n)}static handleNext(e){if(e.length>0){const t=e.pop();BrowseFiles.insertElement(t.fileName,Number(t.uid))}}}export default new BrowseFiles;