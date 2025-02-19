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
import{SeverityEnum as s}from"@typo3/backend/enum/severity.js";import c from"@typo3/core/event/regular-event.js";import m from"@typo3/core/document-service.js";import f from"@typo3/backend/modal.js";class p{constructor(){m.ready().then(()=>{new c("click",(a,e)=>{a.preventDefault();let t=e.dataset.redirectUrl;t=encodeURIComponent(t||top.list_frame.document.location.pathname+top.list_frame.document.location.search);const i=e.dataset.filelistDeleteIdentifier,d=e.dataset.filelistDeleteType,o=e.dataset.filelistDeleteUrl+"&data[delete][0][data]="+encodeURIComponent(i)+"&data[delete][0][redirect]="+t;if(e.dataset.filelistDeleteCheck){const l=f.confirm(e.dataset.title,e.dataset.bsContent,s.warning,[{text:TYPO3.lang["buttons.confirm.delete_file.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm."+d+".yes"]||"Yes, delete this file or folder",btnClass:"btn-warning",name:"yes"}]);l.addEventListener("button.clicked",r=>{const n=r.target.name;n==="no"?l.hideModal():n==="yes"&&(l.hideModal(),top.list_frame.location.href=o)})}else top.list_frame.location.href=o}).delegateTo(document,'[data-filelist-delete="true"]')})}}var u=new p;export{u as default};
