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
var __decorate=function(e,t,l,n){var c,i=arguments.length,o=i<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,l):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,l,n);else for(var r=e.length-1;r>=0;r--)(c=e[r])&&(o=(i<3?c(o):i>3?c(t,l,o):c(t,l))||o);return i>3&&o&&Object.defineProperty(t,l,o),o};import{html,LitElement}from"lit";import{customElement}from"lit/decorators.js";import{lll}from"@typo3/core/lit-helper.js";let SelectTreeToolbar=class extends LitElement{constructor(){super(...arguments),this.settings={collapseAllBtn:"collapse-all-btn",expandAllBtn:"expand-all-btn",searchInput:"search-input",toggleHideUnchecked:"hide-unchecked-btn"},this.hideUncheckedState=!1}createRenderRoot(){return this}render(){return html`
      <div class="tree-toolbar btn-toolbar">
        <div class="input-group">
          <span class="input-group-text input-group-icon filter">
            <typo3-backend-icon identifier="actions-filter" size="small"></typo3-backend-icon>
          </span>
          <input type="search" class="form-control ${this.settings.searchInput}" placeholder="${lll("tcatree.findItem")}" @input="${e=>this.filter(e)}">
        </div>
        <div class="btn-group">
          <button type="button" class="btn btn-default ${this.settings.expandAllBtn}" title="${lll("tcatree.expandAll")}" @click="${()=>this.expandAll()}">
            <typo3-backend-icon identifier="apps-pagetree-category-expand-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" class="btn btn-default ${this.settings.collapseAllBtn}" title="${lll("tcatree.collapseAll")}" @click="${e=>this.collapseAll(e)}">
            <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${lll("tcatree.toggleHideUnchecked")}" @click="${()=>this.toggleHideUnchecked()}">
            <typo3-backend-icon identifier="apps-pagetree-category-toggle-hide-checked" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `}collapseAll(e){e.preventDefault(),this.tree.nodes.forEach((e=>{e.__parents.length&&this.tree.hideChildren(e)}))}expandAll(){this.tree.expandAll()}filter(e){const t=e.target;this.tree.filter(t.value.trim())}toggleHideUnchecked(){this.hideUncheckedState=!this.hideUncheckedState,this.hideUncheckedState?this.tree.nodes.forEach((e=>{e.checked?(this.tree.showParents(e),e.expanded=!0,e.__hidden=!1):(e.expanded=!1,e.__hidden=!0)})):this.tree.nodes.forEach((e=>e.__hidden=!1))}};SelectTreeToolbar=__decorate([customElement("typo3-backend-form-selecttree-toolbar")],SelectTreeToolbar);export{SelectTreeToolbar};