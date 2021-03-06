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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","./PageTree","./PageTreeDragDrop","TYPO3/CMS/Core/Ajax/AjaxRequest","d3-selection","TYPO3/CMS/Core/Event/DebounceEvent","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,r,i,s,a,o,n,l,c){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeElement=void 0,n=__importDefault(n),c=__importDefault(c);class d{constructor(e){const t=document.querySelector(e);if(t&&t.childNodes.length>0)return void t.querySelector(".svg-tree").dispatchEvent(new Event("svg-tree:visible"));r.render(d.renderTemplate(),t);const i=t.querySelector(".svg-tree-wrapper");this.tree=new a.PageTree;const s=new o.PageTreeDragDrop(this.tree),l=top.TYPO3.settings.ajaxUrls.page_tree_configuration;new n.default(l).get().then(async e=>{const r=await e.resolve("json"),a=top.TYPO3.settings.ajaxUrls.page_tree_data,o=top.TYPO3.settings.ajaxUrls.page_tree_filter;Object.assign(r,{dataUrl:a,filterUrl:o,showIcons:!0}),this.tree.initialize(i,r,s);const n=t.querySelector(".svg-toolbar");if(!n.dataset.treeShowToolbar){new h(s).initialize(i,n),n.dataset.treeShowToolbar="true"}})}static renderTemplate(){return i.html`
      <div id="typo3-pagetree" class="svg-tree">
        <div>
          <div id="typo3-pagetree-toolbar" class="svg-toolbar"></div>
          <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
            <div id="typo3-pagetree-tree" class="svg-tree-wrapper">
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
    `}getName(){return"PageTree"}refresh(){this.tree.refreshOrFilterTree()}select(e){this.tree.selectNode(e)}apply(e){e(this.tree)}}t.PageTreeElement=d;class h{constructor(e){this.settings={toolbarSelector:"tree-toolbar",searchInput:".search-input",filterTimeout:450},this.dragDrop=e}initialize(e,t,r={}){this.treeContainer=e,this.targetEl=t,this.treeContainer.dataset.svgTreeInitialized&&"object"==typeof this.treeContainer.svgtree?(Object.assign(this.settings,r),this.render()):this.treeContainer.addEventListener("svg-tree:initialized",()=>this.render())}refreshTree(){this.tree.refreshOrFilterTree()}search(e){this.tree.searchQuery=e.value.trim(),this.tree.refreshOrFilterTree(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}render(){this.tree=this.treeContainer.svgtree,Object.assign(this.settings,this.tree.settings),r.render(this.renderTemplate(),this.targetEl);const e=l.select(".svg-toolbar");this.tree.settings.doktypes.forEach(t=>{t.icon?e.selectAll("[data-tree-icon="+t.icon+"]").call(this.dragToolbar(t)):console.warn("Missing icon definition for doktype: "+t.nodeType)});const t=this.targetEl.querySelector(this.settings.searchInput);t&&(new c.default("input",e=>{this.search(e.target)},this.settings.filterTimeout).bindTo(t),t.focus(),t.clearable({onClear:()=>{this.tree.resetFilter(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}}))}renderTemplate(){return i.html`
      <div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <input type="text" class="form-control form-control-sm search-input" placeholder="${s.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${s.lll("labels.refresh")}">
              <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
        <div class="svg-toolbar__submenu">
          ${this.tree.settings.doktypes&&this.tree.settings.doktypes.length?this.tree.settings.doktypes.map(e=>i.html`
                <div class="svg-toolbar__drag-node" data-tree-icon="${e.icon}" data-node-type="${e.nodeType}"
                     title="${e.title}" tooltip="${e.tooltip}">
                  <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
                </div>
              `):""}
        </div>
      </div>
    `}dragToolbar(e){return this.dragDrop.connectDragHandler(new o.ToolbarDragHandler(e,this.tree,this.dragDrop))}}}));