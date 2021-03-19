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
var __decorate=this&&this.__decorate||function(e,t,r,o){var i,n=arguments.length,s=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(s=(n<3?i(s):n>3?i(t,r,s):i(t,r))||s);return n>3&&s&&Object.defineProperty(t,r,s),s},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-element","TYPO3/CMS/Core/lit-helper","./FileStorageTree","TYPO3/CMS/Core/Event/DebounceEvent","TYPO3/CMS/Backend/Storage/Persistent","../ContextMenu","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,r,o,i,n,s,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageTreeNavigationComponent=t.navigationComponentName=void 0,n=__importDefault(n),s=__importDefault(s),t.navigationComponentName="typo3-backend-navigation-component-filestoragetree";let d=class extends r.LitElement{constructor(){super(...arguments),this.refresh=()=>{this.tree.refreshOrFilterTree()},this.selectFirstNode=()=>{const e=this.tree.nodes[0];e&&this.tree.selectNode(e)},this.treeUpdateRequested=e=>{const t=encodeURIComponent(e.detail.payload.identifier);let r=this.tree.nodes.filter(e=>e.identifier===t)[0];r&&0===this.tree.getSelectedNodes().filter(e=>e.identifier===r.identifier).length&&this.tree.selectNode(r)},this.toggleExpandState=e=>{const t=e.detail.node;t&&s.default.set("BackendComponents.States.FileStorageTree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadContent=e=>{const t=e.detail.node;if(!(null==t?void 0:t.checked))return;window.fsMod.recentIds.file=t.identifier,window.fsMod.navFrameHighlightedID.file=t.stateIdentifier;const r=-1!==window.currentSubScript.indexOf("?")?"&":"?";TYPO3.Backend.ContentContainer.setUrl(window.currentSubScript+r+"id="+t.identifier)},this.showContextMenu=e=>{const t=e.detail.node;t&&a.show(t.itemType,decodeURIComponent(t.identifier),"tree","","",this.tree.getNodeElement(t))},this.selectActiveNode=e=>{const t=window.fsMod.navFrameHighlightedID.file;let r=e.detail.nodes;e.detail.nodes=r.map(e=>(e.stateIdentifier===t&&(e.checked=!0),e))}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:filestoragetree:refresh",this.refresh),document.addEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.addEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested)}disconnectedCallback(){document.removeEventListener("typo3:filestoragetree:refresh",this.refresh),document.removeEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.removeEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested),super.disconnectedCallback()}createRenderRoot(){return this}render(){const e={dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0};return r.html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <typo3-backend-navigation-component-filestoragetree-toolbar .tree="${this.tree}" id="filestoragetree-toolbar" class="svg-toolbar"></typo3-backend-navigation-component-filestoragetree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-filestorage-tree id="typo3-filestoragetree-tree" class="svg-tree-wrapper" .setup=${e}></typo3-backend-filestorage-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `}firstUpdated(){this.toolbar.tree=this.tree,this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadContent),this.tree.addEventListener("typo3:svg-tree:node-context",this.showContextMenu),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActiveNode)}};__decorate([r.query(".svg-tree-wrapper")],d.prototype,"tree",void 0),__decorate([r.query("typo3-backend-navigation-component-filestoragetree-toolbar")],d.prototype,"toolbar",void 0),d=__decorate([r.customElement(t.navigationComponentName)],d),t.FileStorageTreeNavigationComponent=d;let l=class extends r.LitElement{constructor(){super(...arguments),this.tree=null,this.settings={searchInput:".search-input",filterTimeout:450}}createRenderRoot(){return this}firstUpdated(){const e=this.querySelector(this.settings.searchInput);e&&(new n.default("input",e=>{const t=e.target;this.tree.filter(t.value.trim())},this.settings.filterTimeout).bindTo(e),e.focus(),e.clearable({onClear:()=>{this.tree.resetFilter()}}))}render(){return r.html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
            <input type="text" class="form-control form-control-sm search-input" placeholder="${o.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${o.lll("labels.refresh")}">
            <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `}refreshTree(){this.tree.refreshOrFilterTree()}};__decorate([r.property({type:i.FileStorageTree})],l.prototype,"tree",void 0),l=__decorate([r.customElement("typo3-backend-navigation-component-filestoragetree-toolbar")],l)}));