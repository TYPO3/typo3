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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","d3-selection","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/PageTree/PageTreeDragDrop","TYPO3/CMS/Core/Event/DebounceEvent"],(function(e,t,s,r,i,a,n,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeToolbar=void 0,o=__importDefault(o);t.PageTreeToolbar=class{constructor(){this.settings={toolbarSelector:"tree-toolbar",searchInput:".search-input",filterTimeout:450},this.dragDrop=n}initialize(e,t,s={}){this.treeContainer=e,this.targetEl=t,this.treeContainer.dataset.svgTreeInitialized&&"object"==typeof this.treeContainer.svgtree?(Object.assign(this.settings,s),this.render()):this.treeContainer.addEventListener("svg-tree:initialized",()=>this.render())}refreshTree(){this.tree.refreshOrFilterTree()}search(e){this.tree.searchQuery=e.value.trim(),this.tree.refreshOrFilterTree(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}showSubmenu(e){this.targetEl.querySelectorAll("[data-tree-show-submenu]").forEach(t=>{t.dataset.treeShowSubmenu===e?t.classList.add("active"):t.classList.remove("active")}),this.targetEl.querySelectorAll("[data-tree-submenu]").forEach(t=>{t.dataset.treeSubmenu===e?t.classList.add("active"):t.classList.remove("active")});const t=this.targetEl.querySelector('[data-tree-submenu="'+e+'"]').querySelector("input");t&&(t.focus(),t.clearable({onClear:()=>{this.tree.resetFilter(),this.tree.prepareDataForVisibleNodes(),this.tree.update()}}))}render(){this.tree=this.treeContainer.svgtree,Object.assign(this.settings,this.tree.settings),r.render(this.renderTemplate(),this.targetEl);const e=s.select(".svg-toolbar");this.tree.settings.doktypes.forEach((t,s)=>{t.icon?e.selectAll("[data-tree-icon="+t.icon+"]").call(this.dragDrop.dragToolbar()):console.warn("Missing icon definition for doktype: "+t.nodeType)}),new o.default("input",e=>{this.search(e.target)},this.settings.filterTimeout).bindTo(this.targetEl.querySelector(this.settings.searchInput));const t=this.targetEl.querySelector('[data-tree-show-submenu="page-new"]'),i=this.targetEl.querySelector(".svg-toolbar__menu :first-child:not(.js-svg-refresh)");(t||i).click()}renderTemplate(){return i.html`
      <div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="btn-group">
            ${this.tree.settings.doktypes&&this.tree.settings.doktypes.length>0?i.html`
              <div class="x-btn btn btn-default btn-sm x-btn-noicon" data-tree-show-submenu="page-new" @click="${()=>this.showSubmenu("page-new")}">
                <button class="svg-toolbar__btn" data-tree-icon="actions-page-new" title="${a.lll("tree.buttonNewNode")}">
                  ${a.icon("actions-page-new","small")}
                </button>
              </div>
            `:""}
            <div class="x-btn btn btn-default btn-sm x-btn-noicon" data-tree-show-submenu="filter" @click="${()=>this.showSubmenu("filter")}">
              <button class="svg-toolbar__btn" data-tree-icon="actions-filter" title="${a.lll("tree.buttonFilter")}">
                ${a.icon("actions-filter","small")}
              </button>
            </div>
          </div>
          <div class="x-btn btn btn-default btn-sm x-btn-noicon js-svg-refresh" @click="${()=>this.refreshTree()}">
            <button class="svg-toolbar__btn" data-tree-icon="actions-refresh" title="${a.lll("labels.refresh")}">
              ${a.icon("actions-refresh","small")}
            </button>
          </div>
        </div>
        <div class="svg-toolbar__submenu">
          <div class="svg-toolbar__submenu-item" data-tree-submenu="filter">
            <input type="text" class="form-control search-input" placeholder="${a.lll("tree.searchTermInfo")}">
          </div>
          <div class="svg-toolbar__submenu-item" data-tree-submenu="page-new">
            ${this.tree.settings.doktypes&&this.tree.settings.doktypes.length?this.tree.settings.doktypes.map(e=>(this.tree.fetchIcon(e.icon,!1),i.html`
                  <div class="svg-toolbar__drag-node" data-tree-icon="${e.icon}" data-node-type="${e.nodeType}"
                       title="${e.title}" tooltip="${e.tooltip}">
                    ${a.icon(e.icon,"small")}
                  </div>
                `)):""}
          </div>
        </div>
      </div>
    `}}}));