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
import{SeverityEnum as c}from"@typo3/backend/enum/severity.js";import m from"@typo3/core/event/regular-event.js";import f from"@typo3/core/document-service.js";import p from"@typo3/backend/modal.js";import o from"~labels/backend.alt_doc";class u{constructor(){f.ready().then(()=>{new m("click",(i,e)=>{i.preventDefault();let t=e.dataset.redirectUrl;t=encodeURIComponent(t||top.list_frame.document.location.pathname+top.list_frame.document.location.search);const d=e.dataset.filelistDeleteIdentifier,r=e.dataset.filelistDeleteType,n=e.dataset.filelistDeleteUrl+"&data[delete][0][data]="+encodeURIComponent(d)+"&data[delete][0][redirect]="+t;if(e.dataset.filelistDeleteCheck){const l=p.confirm(e.dataset.title,e.dataset.content,c.warning,[{text:o.get("buttons.confirm.delete_file.no"),active:!0,btnClass:"btn-default",name:"no"},{text:r==="delete_folder"?o.get("buttons.confirm.delete_folder.yes"):o.get("buttons.confirm.delete_file.yes"),btnClass:"btn-warning",name:"yes"}]);l.addEventListener("button.clicked",s=>{const a=s.target.name;a==="no"?l.hideModal():a==="yes"&&(l.hideModal(),top.list_frame.location.href=n)})}else top.list_frame.location.href=n}).delegateTo(document,'[data-filelist-delete="true"]')})}}var b=new u;export{b as default};
