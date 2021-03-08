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
var __decorate=this&&this.__decorate||function(e,t,r,i){var s,o=arguments.length,n=o<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,r,i);else for(var a=e.length-1;a>=0;a--)(s=e[a])&&(n=(o<3?s(n):o>3?s(t,r,n):s(t,r))||n);return o>3&&n&&Object.defineProperty(t,r,n),n},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-element","TYPO3/CMS/Core/lit-helper","./FileStorageTree","TYPO3/CMS/Core/Event/DebounceEvent","./FileStorageTreeActions","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,r,i,s,o,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageTreeNavigationComponent=t.navigationComponentName=void 0,o=__importDefault(o),t.navigationComponentName="typo3-backend-navigation-component-filestoragetree";let a=class extends r.LitElement{constructor(){super(),this.tree=null,this.refresh=()=>{this.tree.refreshOrFilterTree()},this.selectFirstNode=()=>{const e=this.tree.nodes[0];e&&this.tree.selectNode(e)},this.treeUpdateRequested=e=>{this.tree.selectNodeByIdentifier(e.detail.payload.identifier)},this.tree=new s.FileStorageTree}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:filestoragetree:refresh",this.refresh),document.addEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.addEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested)}disconnectedCallback(){document.removeEventListener("typo3:filestoragetree:refresh",this.refresh),document.removeEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.removeEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested),super.disconnectedCallback()}createRenderRoot(){return this}render(){return r.html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <typo3-backend-navigation-component-filestoragetree-toolbar .tree="${this.tree}" id="filestoragetree-toolbar" class="svg-toolbar"></typo3-backend-navigation-component-filestoragetree-toolbar>
          <div class="navigation-tree-container">
            <div id="typo3-filestoragetree-tree" class="svg-tree-wrapper">
              <div class="node-loader">
                <typo3-backend-icon identifier="spinner-circle-light" size="small"></typo3-backend-icon>
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `}firstUpdated(){this.treeWrapper.dispatchEvent(new Event("svg-tree:visible")),this.tree.initialize(this.treeWrapper,{dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0},new n.FileStorageTreeActions(this.tree))}};__decorate([r.query(".svg-tree-wrapper")],a.prototype,"treeWrapper",void 0),a=__decorate([r.customElement(t.navigationComponentName)],a),t.FileStorageTreeNavigationComponent=a;let l=class extends r.LitElement{constructor(){super(...arguments),this.tree=null,this.settings={searchInput:".search-input",filterTimeout:450}}createRenderRoot(){return this}firstUpdated(){const e=this.querySelector(this.settings.searchInput);e&&(new o.default("input",e=>{this.search(e.target)},this.settings.filterTimeout).bindTo(e),e.focus(),e.clearable({onClear:()=>{this.tree.resetFilter(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}}))}render(){return r.html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
            <input type="text" class="form-control form-control-sm search-input" placeholder="${i.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${i.lll("labels.refresh")}">
            <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `}refreshTree(){this.tree.refreshOrFilterTree()}search(e){this.tree.searchQuery=e.value.trim(),this.tree.refreshOrFilterTree(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}};__decorate([r.property({type:s.FileStorageTree})],l.prototype,"tree",void 0),l=__decorate([r.customElement("typo3-backend-navigation-component-filestoragetree-toolbar")],l)}));