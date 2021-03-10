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
var __decorate=this&&this.__decorate||function(e,t,r,i){var a,n=arguments.length,s=n<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,i);else for(var l=e.length-1;l>=0;l--)(a=e[l])&&(s=(n<3?a(s):n>3?a(t,r,s):a(t,r))||s);return n>3&&s&&Object.defineProperty(t,r,s),s};define(["require","exports","./SelectTree","bootstrap","lit-element","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,r,i,a,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.SelectTreeElement=void 0;t.SelectTreeElement=class{constructor(e,t,i){this.treeWrapper=null,this.recordField=null,this.tree=null,this.treeWrapper=document.getElementById(e),this.recordField=document.getElementById(t),this.tree=new r.SelectTree;const a={dataUrl:this.generateRequestUrl(),showIcons:!0,readOnlyMode:1===parseInt(this.recordField.dataset.readOnly,10),input:this.recordField,exclusiveNodesIdentifiers:this.recordField.dataset.treeExclusiveKeys,validation:JSON.parse(this.recordField.dataset.formengineValidationRules)[0],expandUpToLevel:this.recordField.dataset.treeExpandUpToLevel,unselectableElements:[]};this.treeWrapper.addEventListener("svg-tree:initialized",()=>{const e=document.createElement("typo3-backend-form-selecttree-toolbar");e.tree=this.tree,this.treeWrapper.prepend(e)}),this.tree.initialize(this.treeWrapper,a),this.tree.dispatch.on("nodeSelectedAfter.requestUpdate",()=>{i()}),this.listenForVisibleTree()}listenForVisibleTree(){if(!this.treeWrapper.offsetParent){let e=this.treeWrapper.closest(".tab-pane").getAttribute("id");if(e){document.querySelector('[aria-controls="'+e+'"]').addEventListener("shown.bs.tab",()=>{this.treeWrapper.dispatchEvent(new Event("svg-tree:visible"))})}}}generateRequestUrl(){const e={tableName:this.recordField.dataset.tablename,fieldName:this.recordField.dataset.fieldname,uid:this.recordField.dataset.uid,recordTypeValue:this.recordField.dataset.recordtypevalue,dataStructureIdentifier:this.recordField.dataset.datastructureidentifier,flexFormSheetName:this.recordField.dataset.flexformsheetname,flexFormFieldName:this.recordField.dataset.flexformfieldname,flexFormContainerName:this.recordField.dataset.flexformcontainername,flexFormContainerIdentifier:this.recordField.dataset.flexformcontaineridentifier,flexFormContainerFieldName:this.recordField.dataset.flexformcontainerfieldname,flexFormSectionContainerIsNew:this.recordField.dataset.flexformsectioncontainerisnew,command:this.recordField.dataset.command};return TYPO3.settings.ajaxUrls.record_tree_data+"&"+new URLSearchParams(e)}};let s=class extends a.LitElement{constructor(){super(...arguments),this.settings={collapseAllBtn:"collapse-all-btn",expandAllBtn:"expand-all-btn",searchInput:"search-input",toggleHideUnchecked:"hide-unchecked-btn"},this.hideUncheckedState=!1}createRenderRoot(){return this}firstUpdated(){this.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(e=>new i.Tooltip(e))}render(){return a.html`
      <div class="tree-toolbar btn-toolbar">
        <div class="input-group">
          <span class="input-group-addon input-group-icon filter">
            <typo3-backend-icon identifier="actions-filter" size="small"></typo3-backend-icon>
          </span>
          <input type="text" class="form-control ${this.settings.searchInput}" placeholder="${n.lll("tcatree.findItem")}" @input="${e=>this.search(e)}">
        </div>
        <div class="btn-group">
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.expandAllBtn}" title="${n.lll("tcatree.expandAll")}" @click="${()=>this.expandAll()}">
            <typo3-backend-icon identifier="apps-pagetree-category-expand-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.collapseAllBtn}" title="${n.lll("tcatree.collapseAll")}" @click="${()=>this.collapseAll()}">
            <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${n.lll("tcatree.toggleHideUnchecked")}" @click="${()=>this.toggleHideUnchecked()}">
            <typo3-backend-icon identifier="apps-pagetree-category-toggle-hide-checked" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `}collapseAll(){this.tree.collapseAll()}expandAll(){this.tree.expandAll()}search(e){const t=e.target;this.tree.nodes.length&&(this.tree.nodes[0].expanded=!1);const r=t.value.trim(),i=new RegExp(r,"i");this.tree.nodes.forEach(e=>{i.test(e.name)?(this.showParents(e),e.expanded=!0,e.hidden=!1):(e.hidden=!0,e.expanded=!1)}),this.tree.prepareDataForVisibleNodes(),this.tree.update()}toggleHideUnchecked(){this.hideUncheckedState=!this.hideUncheckedState,this.hideUncheckedState?this.tree.nodes.forEach(e=>{e.checked?(this.showParents(e),e.expanded=!0,e.hidden=!1):(e.hidden=!0,e.expanded=!1)}):this.tree.nodes.forEach(e=>e.hidden=!1),this.tree.prepareDataForVisibleNodes(),this.tree.update()}showParents(e){if(0===e.parents.length)return;const t=this.tree.nodes[e.parents[0]];t.hidden=!1,t.expanded=!0,this.showParents(t)}};s=__decorate([a.customElement("typo3-backend-form-selecttree-toolbar")],s)}));