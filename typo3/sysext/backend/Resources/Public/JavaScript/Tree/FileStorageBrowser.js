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
var __decorate=this&&this.__decorate||function(e,t,i,r){var n,o=arguments.length,s=o<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,i,r);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(s=(o<3?n(s):o>3?n(t,i,s):n(t,i))||s);return o>3&&s&&Object.defineProperty(t,i,s),s},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Recordlist/ElementBrowser","TYPO3/CMS/Recordlist/LinkBrowser","TYPO3/CMS/Backend/Storage/Persistent","./FileStorageTree","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,i,r,n,o,s,a,c){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageBrowser=void 0,n=__importDefault(n),a=__importDefault(a);let d=class extends c.FileStorageTree{updateNodeActions(e){const t=super.updateNodeActions(e);if(this.settings.actions.includes("link")){this.fetchIcon("actions-link");const e=t.append("g").on("click",(e,t)=>{this.linkItem(t)});this.createIconAreaForAction(e,"actions-link")}else if(this.settings.actions.includes("select")){this.fetchIcon("actions-link");const e=t.append("g").on("click",(e,t)=>{this.selectItem(t)});this.createIconAreaForAction(e,"actions-link")}return t}linkItem(e){s.finalizeFunction("t3://folder?storage="+e.storage+"&identifier="+e.pathIdentifier)}selectItem(e){o.insertElement(e.itemType,e.identifier,e.name,e.identifier,!0)}};d=__decorate([(0,r.customElement)("typo3-backend-component-filestorage-browser-tree")],d);let l=class extends i.LitElement{constructor(){super(...arguments),this.activeFolder="",this.actions=[],this.triggerRender=()=>{this.tree.dispatchEvent(new Event("svg-tree:visible"))},this.selectActiveNode=e=>{let t=e.detail.nodes;e.detail.nodes=t.map(e=>(decodeURIComponent(e.identifier)===this.activeFolder&&(e.checked=!0),e))},this.toggleExpandState=e=>{const t=e.detail.node;t&&a.default.set("BackendComponents.States.FileStorageTree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadFolderDetails=e=>{const t=e.detail.node;if(!t.checked)return;let i=document.location.href+"&contentOnly=1&expandFolder="+t.identifier;new n.default(i).get().then(e=>e.resolve()).then(e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e})}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:navigation:resized",this.triggerRender)}disconnectedCallback(){document.removeEventListener("typo3:navigation:resized",this.triggerRender),super.disconnectedCallback()}firstUpdated(){this.activeFolder=this.getAttribute("active-folder")||""}createRenderRoot(){return this}render(){this.hasAttribute("tree-actions")&&this.getAttribute("tree-actions").length&&(this.actions=JSON.parse(this.getAttribute("tree-actions")));const e={dataUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,showIcons:!0,actions:this.actions};return i.html`
      <div class="svg-tree">
        <div>
          <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-component-filestorage-browser-tree class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{this.tree.dispatchEvent(new Event("svg-tree:visible")),this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadFolderDetails),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActiveNode);this.querySelector("typo3-backend-tree-toolbar").tree=this.tree}}></typo3-backend-component-page-browser-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `}};__decorate([(0,r.query)(".svg-tree-wrapper")],l.prototype,"tree",void 0),l=__decorate([(0,r.customElement)("typo3-backend-component-filestorage-browser")],l),t.FileStorageBrowser=l}));