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
import{Tree}from"@typo3/backend/tree/tree.js";import{html}from"lit";import{DataTransferTypes}from"@typo3/backend/enum/data-transfer-types.js";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";export class PageTree extends Tree{constructor(){super(),this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1,stopPageTree:!1}}getDataUrl(e=null){return null===e?this.settings.dataUrl:this.settings.dataUrl+"&parent="+e.identifier+"&mount="+e.mountPoint+"&depth="+e.depth}createNodeToggle(e){const t=this.isRTL()?"actions-caret-left":"actions-caret-right";return html`${e.stopPageTree&&0!==e.depth?html`
          <span class="node-stop" @click="${t=>{t.preventDefault(),t.stopImmediatePropagation(),document.dispatchEvent(new CustomEvent("typo3:pagetree:mountPoint",{detail:{pageId:parseInt(e.identifier,10)}}))}}">
            <typo3-backend-icon identifier="${t}" size="small"></typo3-backend-icon>
          </span>
        `:super.createNodeToggle(e)}`}handleNodeDragOver(e){if(super.handleNodeDragOver(e))return!0;if(e.dataTransfer.types.includes(DataTransferTypes.content)){const t=this.getNodeFromDragEvent(e);if(null===t)return!1;this.cleanDrag();return this.getElementFromNode(t).classList.add("node-hover"),t.hasChildren&&!t.__expanded?this.openNodeTimeout.targetNode!=t&&(this.openNodeTimeout.targetNode=t,clearTimeout(this.openNodeTimeout.timeout),this.openNodeTimeout.timeout=setTimeout((()=>{this.showChildren(this.openNodeTimeout.targetNode),this.openNodeTimeout.targetNode=null,this.openNodeTimeout.timeout=null}),1e3)):(clearTimeout(this.openNodeTimeout.timeout),this.openNodeTimeout.targetNode=null,this.openNodeTimeout.timeout=null),e.preventDefault(),!0}return!1}handleNodeDrop(e){if(super.handleNodeDrop(e))return!0;if(e.dataTransfer.types.includes(DataTransferTypes.content)){const t=this.getNodeFromDragEvent(e);if(null===t)return!1;const o=e.dataTransfer.getData(DataTransferTypes.content),r=JSON.parse(o);e.preventDefault();const n=new URL(r.moveElementUrl,window.origin);return n.searchParams.set("expandPage",t.identifier),n.searchParams.set("originalPid",t.identifier),Modal.advanced({content:n.toString(),severity:SeverityEnum.notice,size:Modal.sizes.large,type:Modal.types.iframe}),!0}return!1}}