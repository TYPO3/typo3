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
import l from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionSelectors as v}from"@typo3/backend/multi-record-selection.js";import{FileListActionSelector as i,FileListActionUtility as n}from"@typo3/filelist/file-list-actions.js";import{DataTransferTypes as f}from"@typo3/backend/enum/data-transfer-types.js";var c;(function(m){m.transfer="typo3:filelist:resource:dragdrop:transfer"})(c||(c={}));class w{constructor(){this.previewSize=32;const t=i.elementSelector+'[draggable="true"]';new l("dragstart",(e,s)=>{const r=[];let d="",u="";const p=document.querySelectorAll(v.checkboxSelector+":checked");if(p.length)p.forEach(a=>{if(a.checked){const o=a.closest(i.elementSelector);o.dataset.filelistDragdropTransferItem="true";const h=n.getResourceForElement(o);r.push(h),u=o.dataset.filelistName,d=o.dataset.filelistIcon}});else{const a=s.closest(i.elementSelector);a.dataset.filelistDragdropTransferItem="true";const o=n.getResourceForElement(a);r.push(o),u=a.dataset.filelistName,d=a.dataset.filelistIcon}e.dataTransfer.effectAllowed="move",e.dataTransfer.setData(f.falResources,JSON.stringify(r));const g={tooltipIconIdentifier:r.length>1?"apps-clipboard-images":d,tooltipLabel:r.length>1?this.getPreviewLabel(r):u,thumbnails:this.getPreviewItems(r)};e.dataTransfer.setData(f.dragTooltip,JSON.stringify(g))}).delegateTo(document,t),new l("dragover",(e,s)=>{const r=n.getResourceForElement(s);this.isDropAllowedOnResoruce(r)&&(e.dataTransfer.dropEffect="move",e.preventDefault(),s.classList.add("success"))},{capture:!0}).delegateTo(document,t),new l("drop",(e,s)=>{const r={action:"transfer",resources:JSON.parse(e.dataTransfer.getData(f.falResources)??"{}"),target:n.getResourceForElement(s)};top.document.dispatchEvent(new CustomEvent(c.transfer,{detail:r}))},{capture:!0,passive:!0}).delegateTo(document,t),new l("dragend",()=>{this.reset()},{capture:!0,passive:!0}).delegateTo(document,t),new l("dragleave",(e,s)=>{s.classList.remove("success")},{capture:!0,passive:!0}).delegateTo(document,t)}getPreviewItems(t){return t.filter(e=>e.thumbnail!==null).map(e=>({src:e.thumbnail,width:this.previewSize,height:this.previewSize}))}getPreviewLabel(t){const e=t.filter(r=>r.thumbnail!==null),s=t.length-e.length;return s>0?(e.length>0?"+":"")+s.toString():""}reset(){document.querySelectorAll(i.elementSelector).forEach(t=>{delete t.dataset.filelistDragdropTransferItem,t.classList.remove("success")})}isDropAllowedOnResoruce(t){return!("filelistDragdropTransferItem"in document.querySelector(i.elementSelector+'[data-filelist-identifier="'+t.identifier+'"]').dataset)&&t.type==="folder"}}var S=new w;export{c as FileListDragDropEvent,S as default};
