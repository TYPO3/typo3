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
var __decorate=function(e,t,s,i){var n,d=arguments.length,o=d<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,s):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,s,i);else for(var c=e.length-1;c>=0;c--)(n=e[c])&&(o=(d<3?n(o):d>3?n(t,s,o):n(t,s))||o);return d>3&&o&&Object.defineProperty(t,s,o),o};import{html}from"lit";import{Tree}from"@typo3/backend/tree/tree.js";import{customElement,state}from"lit/decorators.js";let SelectTree=class extends Tree{constructor(){super(),this.settings={unselectableElements:[],exclusiveNodesIdentifiers:"",validation:{},readOnlyMode:!1,showIcons:!0,width:300,dataUrl:"",defaultProperties:{},expandUpToLevel:null},this.exclusiveSelectedNode=null,this.addEventListener("typo3:tree:nodes-prepared",this.prepareLoadedNodes)}expandAll(){this.nodes.forEach((e=>{this.showChildren(e)}))}selectNode(e,t=!0){if(!this.isNodeSelectable(e))return;const s=e.checked;this.handleExclusiveNodeSelection(e),this.settings.validation&&this.settings.validation.maxItems&&!s&&this.getSelectedNodes().length>=this.settings.validation.maxItems||(e.checked=!s,this.dispatchEvent(new CustomEvent("typo3:tree:node-selected",{detail:{node:e,propagate:t}})))}filter(e){this.searchTerm=e,this.nodes.length&&(this.nodes[0].__expanded=!1);const t=new RegExp(e,"i");this.nodes.forEach((e=>{t.test(e.name)?(this.showParents(e),e.__expanded=!0,e.__hidden=!1):(e.__expanded=!1,e.__hidden=!0)}))}showParents(e){if(0===e.parents.length)return;const t=this.nodes[e.parents[0]];t.__hidden=!1,t.__expanded=!0,this.showParents(t)}isNodeSelectable(e){return!this.settings.readOnlyMode&&-1===this.settings.unselectableElements.indexOf(e.identifier)}createNodeContent(e){return html`
      ${this.renderCheckbox(e)}
      ${super.createNodeContent(e)}
    `}renderCheckbox(e){const t=Boolean(e.checked);let s="actions-square";return this.isNodeSelectable(e)||t?e.checked?s="actions-check-square":e.__indeterminate&&!t&&(s="actions-minus-square"):s="actions-minus-circle",html`
      <span class="node-select">
        <typo3-backend-icon identifier="${s}" size="small"></typo3-backend-icon>
      </span>
    `}prepareLoadedNodes(e){const t=e.detail.nodes;e.detail.nodes=t.map((e=>(!1===e.selectable&&this.settings.unselectableElements.push(e.identifier),e)))}handleExclusiveNodeSelection(e){const t=this.settings.exclusiveNodesIdentifiers.split(",");this.settings.exclusiveNodesIdentifiers.length&&!1===e.checked&&(t.indexOf(""+e.identifier)>-1?(this.resetSelectedNodes(),this.exclusiveSelectedNode=e):-1===t.indexOf(""+e.identifier)&&this.exclusiveSelectedNode&&(this.exclusiveSelectedNode.checked=!1,this.exclusiveSelectedNode=null))}};__decorate([state()],SelectTree.prototype,"settings",void 0),__decorate([state()],SelectTree.prototype,"exclusiveSelectedNode",void 0),SelectTree=__decorate([customElement("typo3-backend-form-selecttree")],SelectTree);export{SelectTree};