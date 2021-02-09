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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","d3-selection","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Core/Event/DebounceEvent","TYPO3/CMS/Backend/PageTree/PageTreeDragHandler"],(function(e,t,r,s,i,o,a,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeToolbar=void 0,a=__importDefault(a);t.PageTreeToolbar=class{constructor(e){this.settings={toolbarSelector:"tree-toolbar",searchInput:".search-input",filterTimeout:450},this.dragDrop=e}initialize(e,t,r={}){this.treeContainer=e,this.targetEl=t,this.treeContainer.dataset.svgTreeInitialized&&"object"==typeof this.treeContainer.svgtree?(Object.assign(this.settings,r),this.render()):this.treeContainer.addEventListener("svg-tree:initialized",()=>this.render())}refreshTree(){this.tree.refreshOrFilterTree()}search(e){this.tree.searchQuery=e.value.trim(),this.tree.refreshOrFilterTree(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}render(){this.tree=this.treeContainer.svgtree,Object.assign(this.settings,this.tree.settings),s.render(this.renderTemplate(),this.targetEl);const e=r.select(".svg-toolbar");this.tree.settings.doktypes.forEach(t=>{t.icon?e.selectAll("[data-tree-icon="+t.icon+"]").call(this.dragToolbar(t)):console.warn("Missing icon definition for doktype: "+t.nodeType)});const t=this.targetEl.querySelector(this.settings.searchInput);t&&(new a.default("input",e=>{this.search(e.target)},this.settings.filterTimeout).bindTo(t),t.focus(),t.clearable({onClear:()=>{this.tree.resetFilter(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}}))}renderTemplate(){return i.html`
      <div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <input type="text" class="form-control form-control-sm search-input" placeholder="${o.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${o.lll("labels.refresh")}">
              ${o.icon("actions-refresh","small")}
          </button>
        </div>
        <div class="svg-toolbar__submenu">
          ${this.tree.settings.doktypes&&this.tree.settings.doktypes.length?this.tree.settings.doktypes.map(e=>(this.tree.fetchIcon(e.icon,!1),i.html`
                <div class="svg-toolbar__drag-node" data-tree-icon="${e.icon}" data-node-type="${e.nodeType}"
                     title="${e.title}" tooltip="${e.tooltip}">
                  ${o.icon(e.icon,"small")}
                </div>
              `)):""}
        </div>
      </div>
    `}dragToolbar(e){return this.dragDrop.connectDragHandler(new n.ToolbarDragHandler(e,this.tree,this.dragDrop))}}}));