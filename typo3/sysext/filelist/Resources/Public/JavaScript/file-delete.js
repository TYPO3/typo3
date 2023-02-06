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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import Modal from"@typo3/backend/modal.js";class FileDelete{constructor(){DocumentService.ready().then((()=>{new RegularEvent("click",((e,t)=>{e.preventDefault();let n=t.dataset.redirectUrl;n=n?encodeURIComponent(n):encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search);const o=t.dataset.filelistDeleteIdentifier,a=t.dataset.filelistDeleteType,l=t.dataset.filelistDeleteUrl+"&data[delete][0][data]="+encodeURIComponent(o)+"&data[delete][0][redirect]="+n;if(t.dataset.filelistDeleteCheck){const e=Modal.confirm(t.dataset.title,t.dataset.bsContent,SeverityEnum.warning,[{text:TYPO3.lang["buttons.confirm.delete_file.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm."+a+".yes"]||"Yes, delete this file or folder",btnClass:"btn-warning",name:"yes"}]);e.addEventListener("button.clicked",(t=>{const n=t.target.name;"no"===n?e.hideModal():"yes"===n&&(e.hideModal(),top.list_frame.location.href=l)}))}else top.list_frame.location.href=l})).delegateTo(document,'[data-filelist-delete="true"]')}))}}export default new FileDelete;