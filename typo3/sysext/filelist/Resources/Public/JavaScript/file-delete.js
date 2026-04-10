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
import{SeverityEnum as m}from"@typo3/backend/enum/severity.js";import f from"@typo3/core/event/regular-event.js";import u from"@typo3/core/document-service.js";import p from"@typo3/backend/modal.js";import n from"~labels/backend.alt_doc";import{topWindow as l}from"@typo3/core/utility/top-window.js";class b{constructor(){u.ready().then(()=>{new f("click",(d,e)=>{d.preventDefault();let t=e.dataset.redirectUrl;t=encodeURIComponent(t||l.list_frame.document.location.pathname+l.list_frame.document.location.search);const r=e.dataset.filelistDeleteIdentifier,s=e.dataset.filelistDeleteType,i=e.dataset.filelistDeleteUrl+"&data[delete][0][data]="+encodeURIComponent(r)+"&data[delete][0][redirect]="+t;if(e.dataset.filelistDeleteCheck){const o=p.confirm(e.dataset.title,e.dataset.content,m.warning,[{text:n.get("buttons.confirm.delete_file.no"),active:!0,btnClass:"btn-default",name:"no"},{text:s==="delete_folder"?n.get("buttons.confirm.delete_folder.yes"):n.get("buttons.confirm.delete_file.yes"),btnClass:"btn-warning",name:"yes"}]);o.addEventListener("button.clicked",c=>{const a=c.target.name;a==="no"?o.hideModal():a==="yes"&&(o.hideModal(),l.list_frame.location.href=i)})}else l.list_frame.location.href=i}).delegateTo(document,'[data-filelist-delete="true"]')})}}var D=new b;export{D as default};
