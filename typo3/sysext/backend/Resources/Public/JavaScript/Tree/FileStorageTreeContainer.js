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
var __decorate=this&&this.__decorate||function(e,t,r,o){var i,s=arguments.length,n=s<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(n=(s<3?i(n):s>3?i(t,r,n):i(t,r))||n);return s>3&&n&&Object.defineProperty(t,r,n),n},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-element","TYPO3/CMS/Core/lit-helper","./FileStorageTree","TYPO3/CMS/Core/Event/DebounceEvent","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,r,o,i,s){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageTreeNavigationComponent=t.navigationComponentName=void 0,s=__importDefault(s),t.navigationComponentName="typo3-backend-navigation-component-filestoragetree";let n=class extends r.LitElement{constructor(){super(...arguments),this.refresh=()=>{this.tree.refreshOrFilterTree()},this.selectFirstNode=()=>{const e=this.tree.nodes[0];e&&this.tree.selectNode(e)},this.treeUpdateRequested=e=>{this.tree.selectNodeByIdentifier(e.detail.payload.identifier)}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:filestoragetree:refresh",this.refresh),document.addEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.addEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested)}disconnectedCallback(){document.removeEventListener("typo3:filestoragetree:refresh",this.refresh),document.removeEventListener("typo3:filestoragetree:selectFirstNode",this.selectFirstNode),document.removeEventListener("typo3:filelist:treeUpdateRequested",this.treeUpdateRequested),super.disconnectedCallback()}createRenderRoot(){return this}render(){const e={dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0};return r.html`
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
    `}firstUpdated(){this.toolbar.tree=this.tree}};__decorate([r.query(".svg-tree-wrapper")],n.prototype,"tree",void 0),__decorate([r.query("typo3-backend-navigation-component-filestoragetree-toolbar")],n.prototype,"toolbar",void 0),n=__decorate([r.customElement(t.navigationComponentName)],n),t.FileStorageTreeNavigationComponent=n;let a=class extends r.LitElement{constructor(){super(...arguments),this.tree=null,this.settings={searchInput:".search-input",filterTimeout:450}}createRenderRoot(){return this}firstUpdated(){const e=this.querySelector(this.settings.searchInput);e&&(new s.default("input",e=>{const t=e.target;this.tree.filter(t.value.trim())},this.settings.filterTimeout).bindTo(e),e.focus(),e.clearable({onClear:()=>{this.tree.resetFilter()}}))}render(){return r.html`
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
    `}refreshTree(){this.tree.refreshOrFilterTree()}};__decorate([r.property({type:i.FileStorageTree})],a.prototype,"tree",void 0),a=__decorate([r.customElement("typo3-backend-navigation-component-filestoragetree-toolbar")],a)}));