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
var __decorate=function(e,t,r,o){var i,n=arguments.length,s=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(s=(n<3?i(s):n>3?i(t,r,s):i(t,r))||s);return n>3&&s&&Object.defineProperty(t,r,s),s};import{html,LitElement}from"lit";import{customElement,query}from"lit/decorators.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ElementBrowser from"@typo3/backend/element-browser.js";import LinkBrowser from"@typo3/backend/link-browser.js";import"@typo3/backend/element/icon-element.js";import Persistent from"@typo3/backend/storage/persistent.js";import{FileStorageTree}from"@typo3/backend/tree/file-storage-tree.js";let FileStorageBrowserTree=class extends FileStorageTree{updateNodeActions(e){const t=super.updateNodeActions(e);if(this.settings.actions.includes("link")){const e=t.append("g").on("click",((e,t)=>{this.linkItem(t)}));this.createIconAreaForAction(e,"actions-link")}else if(this.settings.actions.includes("select")){const e=t.append("g").on("click",((e,t)=>{this.selectItem(t)}));this.createIconAreaForAction(e,"actions-link")}return t}linkItem(e){LinkBrowser.finalizeFunction("t3://folder?storage="+e.storage+"&identifier="+e.pathIdentifier)}selectItem(e){ElementBrowser.insertElement(e.itemType,e.identifier,e.name,e.identifier,!0)}};FileStorageBrowserTree=__decorate([customElement("typo3-backend-component-filestorage-browser-tree")],FileStorageBrowserTree);export{FileStorageBrowserTree};let FileStorageBrowser=class extends LitElement{constructor(){super(...arguments),this.activeFolder="",this.actions=[],this.triggerRender=()=>{this.tree.dispatchEvent(new Event("svg-tree:visible"))},this.selectActiveNode=e=>{const t=e.detail.nodes;e.detail.nodes=t.map((e=>(decodeURIComponent(e.identifier)===this.activeFolder&&(e.checked=!0),e)))},this.toggleExpandState=e=>{const t=e.detail.node;t&&Persistent.set("BackendComponents.States.FileStorageTree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadFolderDetails=e=>{const t=e.detail.node;if(!t.checked)return;const r=document.location.href+"&contentOnly=1&expandFolder="+t.identifier;new AjaxRequest(r).get().then((e=>e.resolve())).then((e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e}))}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:navigation:resized",this.triggerRender)}disconnectedCallback(){document.removeEventListener("typo3:navigation:resized",this.triggerRender),super.disconnectedCallback()}firstUpdated(){this.activeFolder=this.getAttribute("active-folder")||""}createRenderRoot(){return this}render(){this.hasAttribute("tree-actions")&&this.getAttribute("tree-actions").length&&(this.actions=JSON.parse(this.getAttribute("tree-actions")));const e={dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0,actions:this.actions};return html`
      <div class="svg-tree">
        <div>
          <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-component-filestorage-browser-tree class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{this.tree.dispatchEvent(new Event("svg-tree:visible")),this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadFolderDetails),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActiveNode);this.querySelector("typo3-backend-tree-toolbar").tree=this.tree}}></typo3-backend-component-page-browser-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle" size="large"></typo3-backend-icon>
        </div>
      </div>
    `}};__decorate([query(".svg-tree-wrapper")],FileStorageBrowser.prototype,"tree",void 0),FileStorageBrowser=__decorate([customElement("typo3-backend-component-filestorage-browser")],FileStorageBrowser);export{FileStorageBrowser};