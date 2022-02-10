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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import Modal from"@typo3/backend/modal.js";class FileDelete{constructor(){DocumentService.ready().then(()=>{new RegularEvent("click",(e,t)=>{e.preventDefault();let o=t.dataset.redirectUrl;o=o?encodeURIComponent(o):encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search);const n=t.dataset.identifier,a=t.dataset.deleteType,l=t.dataset.deleteUrl+"&data[delete][0][data]="+encodeURIComponent(n)+"&data[delete][0][redirect]="+o;if(t.dataset.check){Modal.confirm(t.dataset.title,t.dataset.bsContent,SeverityEnum.warning,[{text:TYPO3.lang["buttons.confirm.delete_file.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm."+a+".yes"]||"Yes, delete this file or folder",btnClass:"btn-warning",name:"yes"}]).on("button.clicked",e=>{const t=e.target.name;"no"===t?Modal.dismiss():"yes"===t&&(Modal.dismiss(),top.list_frame.location.href=l)})}else top.list_frame.location.href=l}).delegateTo(document,".t3js-filelist-delete")})}}export default new FileDelete;