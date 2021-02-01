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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","./FileStorageTree","../Viewport","TYPO3/CMS/Core/Event/DebounceEvent","./FileStorageTreeActions"],(function(e,t,r,i,s,a,l,n,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageTreeContainer=void 0,l=__importDefault(l),n=__importDefault(n);class d{static initialize(e){const t=document.querySelector(e);if(t&&t.childNodes.length>0)return void t.querySelector(".svg-tree").dispatchEvent(new Event("svg-tree:visible"));r.render(d.renderTemplate(),t);const i=t.querySelector(".svg-tree-wrapper"),s=new a.FileStorageTree,n=new o.FileStorageTreeActions(s);s.initialize(i,{dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0},n),l.default.NavigationContainer.setComponentInstance(s);const h=t.querySelector(".svg-toolbar");new c(i,h),document.addEventListener("typo3:filelist:treeUpdateRequested",e=>{s.selectNodeByIdentifier(e.detail.payload.identifier)})}static renderTemplate(){return i.html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <div id="filestoragetree-toolbar" class="svg-toolbar"></div>
          <div class="navigation-tree-container">
            <div id="typo3-filestoragetree-tree" class="svg-tree-wrapper">
              <div class="node-loader">
                ${s.icon("spinner-circle-light","small")}
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          ${s.icon("spinner-circle-light","large")}
        </div>
      </div>
    `}}t.FileStorageTreeContainer=d;class c{constructor(e,t){this.settings={toolbarSelector:"tree-toolbar",searchInput:".search-input",filterTimeout:450},this.treeContainer=e,this.targetEl=t,this.treeContainer.dataset.svgTreeInitialized&&"object"==typeof this.treeContainer.svgtree?this.render():this.treeContainer.addEventListener("svg-tree:initialized",this.render.bind(this))}refreshTree(){this.tree.refreshOrFilterTree()}search(e){this.tree.searchQuery=e.value.trim(),this.tree.refreshOrFilterTree(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}render(){this.tree=this.treeContainer.svgtree,Object.assign(this.settings,this.tree.settings),r.render(this.renderTemplate(),this.targetEl);const e=this.targetEl.querySelector(this.settings.searchInput);e&&(new n.default("input",e=>{this.search(e.target)},this.settings.filterTimeout).bindTo(e),e.focus(),e.clearable({onClear:()=>{this.tree.resetFilter(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}}))}renderTemplate(){return i.html`<div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
            <input type="text" class="form-control form-control-sm search-input" placeholder="${s.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${s.lll("labels.refresh")}">
            ${s.icon("actions-refresh","small")}
          </button>
        </div>
      </div>`}}}));