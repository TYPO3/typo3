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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","bootstrap","lit-html","lit-element","TYPO3/CMS/Core/lit-helper"],(function(e,t,s,l,i,n,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.TreeToolbar=void 0,s=__importDefault(s);t.TreeToolbar=class{constructor(e={}){this.settings={toolbarSelector:"tree-toolbar btn-toolbar",collapseAllBtn:"collapse-all-btn",expandAllBtn:"expand-all-btn",searchInput:"search-input",toggleHideUnchecked:"hide-unchecked-btn"},this.hideUncheckedState=!1,Object.assign(this.settings,e)}initialize(e){this.treeContainer=e,this.$treeWrapper=s.default(e),this.$treeWrapper.data("svgtree-initialized")&&"object"==typeof this.$treeWrapper.data("svgtree")?this.render():this.$treeWrapper.on("svgTree.initialized",()=>this.render())}collapseAll(){this.tree.collapseAll()}expandAll(){this.tree.expandAll()}search(e){const t=e.target;this.tree.nodes.length&&(this.tree.nodes[0].open=!1);const s=t.value.trim(),l=new RegExp(s,"i");this.tree.nodes.forEach(e=>{l.test(e.name)?(this.showParents(e),e.open=!0,e.hidden=!1):(e.hidden=!0,e.open=!1)}),this.tree.prepareDataForVisibleNodes(),this.tree.update()}toggleHideUnchecked(){this.hideUncheckedState=!this.hideUncheckedState,this.hideUncheckedState?this.tree.nodes.forEach(e=>{e.checked?(this.showParents(e),e.expanded=!0,e.hidden=!1):(e.hidden=!0,e.expanded=!1)}):this.tree.nodes.forEach(e=>e.hidden=!1),this.tree.prepareDataForVisibleNodes(),this.tree.update()}showParents(e){if(0===e.parents.length)return;const t=this.tree.nodes[e.parents[0]];t.hidden=!1,t.expanded=!0,this.showParents(t)}render(){this.tree=this.$treeWrapper.data("svgtree"),Object.assign(this.settings,this.tree.settings);const e=document.createElement("div");this.treeContainer.prepend(e),i.render(this.renderTemplate(),e);const t=this.treeContainer.querySelector("."+this.settings.toolbarSelector);t&&t.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(e=>new l.Tooltip(e))}renderTemplate(){return n.html`
      <div class="${this.settings.toolbarSelector}">
        <div class="input-group">
          <span class="input-group-addon input-group-icon filter">${r.icon("actions-filter","small")}</span>
          <input type="text" class="form-control ${this.settings.searchInput}" placeholder="${r.lll("tcatree.findItem")}" @input="${e=>this.search(e)}">
        </div>
        <div class="btn-group">
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.expandAllBtn}" title="${r.lll("tcatree.expandAll")}" @click="${()=>this.expandAll()}">
            ${r.icon("apps-pagetree-category-expand-all","small")}
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.collapseAllBtn}" title="${r.lll("tcatree.collapseAll")}" @click="${()=>this.collapseAll()}">
            ${r.icon("apps-pagetree-category-collapse-all","small")}
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${r.lll("tcatree.toggleHideUnchecked")}" @click="${()=>this.toggleHideUnchecked()}">
            ${r.icon("apps-pagetree-category-toggle-hide-checked","small")}
          </button>
        </div>
      </div>
    `}}}));