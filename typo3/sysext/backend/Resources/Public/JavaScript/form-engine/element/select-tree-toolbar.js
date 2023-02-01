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
var __decorate=function(e,t,l,i){var n,o=arguments.length,r=o<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,l):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)r=Reflect.decorate(e,t,l,i);else for(var c=e.length-1;c>=0;c--)(n=e[c])&&(r=(o<3?n(r):o>3?n(t,l,r):n(t,l))||r);return o>3&&r&&Object.defineProperty(t,l,r),r};import{html,LitElement}from"lit";import{customElement}from"lit/decorators.js";import{lll}from"@typo3/core/lit-helper.js";let SelectTreeToolbar=class extends LitElement{constructor(){super(...arguments),this.settings={collapseAllBtn:"collapse-all-btn",expandAllBtn:"expand-all-btn",searchInput:"search-input",toggleHideUnchecked:"hide-unchecked-btn"},this.hideUncheckedState=!1}createRenderRoot(){return this}render(){return html`
      <div class="tree-toolbar btn-toolbar">
        <div class="input-group">
          <span class="input-group-addon input-group-icon filter">
            <typo3-backend-icon identifier="actions-filter" size="small"></typo3-backend-icon>
          </span>
          <input type="text" class="form-control ${this.settings.searchInput}" placeholder="${lll("tcatree.findItem")}" @input="${e=>this.filter(e)}">
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
    `}collapseAll(e){e.preventDefault(),this.tree.nodes.forEach((e=>{e.parents.length&&this.tree.hideChildren(e)})),this.tree.prepareDataForVisibleNodes(),this.tree.updateVisibleNodes()}expandAll(){this.tree.expandAll()}filter(e){const t=e.target;this.tree.filter(t.value.trim())}toggleHideUnchecked(){this.hideUncheckedState=!this.hideUncheckedState,this.hideUncheckedState?this.tree.nodes.forEach((e=>{e.checked?(this.tree.showParents(e),e.expanded=!0,e.hidden=!1):(e.hidden=!0,e.expanded=!1)})):this.tree.nodes.forEach((e=>e.hidden=!1)),this.tree.prepareDataForVisibleNodes(),this.tree.updateVisibleNodes()}};SelectTreeToolbar=__decorate([customElement("typo3-backend-form-selecttree-toolbar")],SelectTreeToolbar);export{SelectTreeToolbar};