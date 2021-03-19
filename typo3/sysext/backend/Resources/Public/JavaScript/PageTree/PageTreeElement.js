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
var __decorate=this&&this.__decorate||function(e,t,o,n){var r,i=arguments.length,s=i<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,o):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,o,n);else for(var a=e.length-1;a>=0;a--)(r=e[a])&&(s=(i<3?r(s):i>3?r(t,o,s):r(t,o))||s);return i>3&&s&&Object.defineProperty(t,o,s),s},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-element","lit-html/directives/until","TYPO3/CMS/Core/lit-helper","./PageTree","./PageTreeDragDrop","TYPO3/CMS/Core/Ajax/AjaxRequest","d3-selection","TYPO3/CMS/Core/Event/DebounceEvent","TYPO3/CMS/Backend/Storage/Persistent","../ContextMenu","TYPO3/CMS/Backend/Element/IconElement","TYPO3/CMS/Backend/Input/Clearable"],(function(e,t,o,n,r,i,s,a,d,l,c,p){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeNavigationComponent=t.navigationComponentName=void 0,a=__importDefault(a),l=__importDefault(l),c=__importDefault(c),t.navigationComponentName="typo3-backend-navigation-component-pagetree";let h=class extends o.LitElement{constructor(){super(...arguments),this.mountPointPath=null,this.configuration=null,this.refresh=()=>{this.tree.refreshOrFilterTree()},this.setMountPoint=e=>{this.setTemporaryMountPoint(e.detail.pageId)},this.selectFirstNode=()=>{const e=this.tree.nodes[0];e&&this.tree.selectNode(e)},this.toggleExpandState=e=>{const t=e.detail.node;t&&c.default.set("BackendComponents.States.Pagetree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadContent=e=>{const t=e.detail.node;if(!(null==t?void 0:t.checked))return;top.window.fsMod.recentIds.web=t.identifier,top.window.fsMod.currentBank=t.stateIdentifier.split("_")[0],top.window.fsMod.navFrameHighlightedID.web=t.stateIdentifier;let o="?";-1!==top.window.currentSubScript.indexOf("?")&&(o="&"),top.TYPO3.Backend.ContentContainer.setUrl(top.window.currentSubScript+o+"id="+t.identifier)},this.showContextMenu=e=>{const t=e.detail.node;t&&p.show(t.itemType,parseInt(t.identifier,10),"tree","","",this.tree.getNodeElement(t))},this.selectActiveNode=e=>{const t=window.fsMod.navFrameHighlightedID.web;let o=e.detail.nodes;e.detail.nodes=o.map(e=>(e.stateIdentifier===t&&(e.checked=!0),e))}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:pagetree:refresh",this.refresh),document.addEventListener("typo3:pagetree:mountPoint",this.setMountPoint),document.addEventListener("typo3:pagetree:selectFirstNode",this.selectFirstNode)}disconnectedCallback(){document.removeEventListener("typo3:pagetree:refresh",this.refresh),document.removeEventListener("typo3:pagetree:mountPoint",this.setMountPoint),document.removeEventListener("typo3:pagetree:selectFirstNode",this.selectFirstNode),super.disconnectedCallback()}createRenderRoot(){return this}render(){return o.html`
      <div id="typo3-pagetree" class="svg-tree">
        ${n.until(this.renderTree(),this.renderLoader())}
      </div>
    `}getConfiguration(){if(null!==this.configuration)return Promise.resolve(this.configuration);const e=top.TYPO3.settings.ajaxUrls.page_tree_configuration;return new a.default(e).get().then(async e=>{const t=await e.resolve("json");return Object.assign(t,{dataUrl:top.TYPO3.settings.ajaxUrls.page_tree_data,filterUrl:top.TYPO3.settings.ajaxUrls.page_tree_filter,showIcons:!0}),this.configuration=t,this.mountPointPath=t.temporaryMountPoint||null,t})}renderTree(){return this.getConfiguration().then(e=>o.html`
          <div>
            <div id="typo3-pagetree-toolbar" class="svg-toolbar">
                <typo3-backend-navigation-component-pagetree-toolbar .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
            </div>
            <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-page-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{const e=new s.PageTreeDragDrop(this.tree);this.tree.dragDrop=e,this.toolbar.tree=this.tree,this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadContent),this.tree.addEventListener("typo3:svg-tree:node-context",this.showContextMenu),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActiveNode)}}></typo3-backend-page-tree>
            </div>
          </div>
          ${this.renderLoader()}
        `)}renderLoader(){return o.html`
      <div class="svg-tree-loader">
        <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
      </div>
    `}unsetTemporaryMountPoint(){this.mountPointPath=null,c.default.unset("pageTree_temporaryMountPoint").then(()=>{this.tree.refreshTree()})}renderMountPoint(){return null===this.mountPointPath?o.html``:o.html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${()=>this.unsetTemporaryMountPoint()}" title="${r.lll("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `}setTemporaryMountPoint(e){new a.default(top.TYPO3.settings.ajaxUrls.page_tree_set_temporary_mount_point).post("pid="+e,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then(e=>e.resolve()).then(e=>{e&&e.hasErrors?(this.tree.errorNotification(e.message,!0),this.tree.updateVisibleNodes()):(this.mountPointPath=e.mountPointPath,this.tree.refreshOrFilterTree())}).catch(e=>{this.tree.errorNotification(e,!0)})}};__decorate([o.property({type:String})],h.prototype,"mountPointPath",void 0),__decorate([o.query(".svg-tree-wrapper")],h.prototype,"tree",void 0),__decorate([o.query("typo3-backend-navigation-component-pagetree-toolbar")],h.prototype,"toolbar",void 0),h=__decorate([o.customElement(t.navigationComponentName)],h),t.PageTreeNavigationComponent=h;let u=class extends o.LitElement{constructor(){super(...arguments),this.tree=null,this.settings={searchInput:".search-input",filterTimeout:450}}initializeDragDrop(e){var t,o,n;(null===(n=null===(o=null===(t=this.tree)||void 0===t?void 0:t.settings)||void 0===o?void 0:o.doktypes)||void 0===n?void 0:n.length)&&this.tree.settings.doktypes.forEach(t=>{if(t.icon){const o=this.querySelector('[data-tree-icon="'+t.icon+'"]');d.select(o).call(this.dragToolbar(t,e))}else console.warn("Missing icon definition for doktype: "+t.nodeType)})}createRenderRoot(){return this}firstUpdated(){const e=this.querySelector(this.settings.searchInput);e&&(new l.default("input",e=>{const t=e.target;this.tree.filter(t.value.trim())},this.settings.filterTimeout).bindTo(e),e.focus(),e.clearable({onClear:()=>{this.tree.resetFilter()}}))}updated(e){e.forEach((e,t)=>{"tree"===t&&null!==this.tree&&this.initializeDragDrop(this.tree.dragDrop)})}render(){var e,t,n;return o.html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <input type="text" class="form-control form-control-sm search-input" placeholder="${r.lll("tree.searchTermInfo")}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${()=>this.refreshTree()}" data-tree-icon="actions-refresh" title="${r.lll("labels.refresh")}">
              <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
        <div class="svg-toolbar__submenu">
          ${(null===(n=null===(t=null===(e=this.tree)||void 0===e?void 0:e.settings)||void 0===t?void 0:t.doktypes)||void 0===n?void 0:n.length)?this.tree.settings.doktypes.map(e=>o.html`
                <div class="svg-toolbar__drag-node" data-tree-icon="${e.icon}" data-node-type="${e.nodeType}"
                     title="${e.title}" tooltip="${e.tooltip}">
                  <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
                </div>
              `):""}
        </div>
      </div>
    `}refreshTree(){this.tree.refreshOrFilterTree()}dragToolbar(e,t){return t.connectDragHandler(new s.ToolbarDragHandler(e,this.tree,t))}};__decorate([o.property({type:i.PageTree})],u.prototype,"tree",void 0),u=__decorate([o.customElement("typo3-backend-navigation-component-pagetree-toolbar")],u)}));