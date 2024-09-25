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
var __decorate=function(e,t,o,n){var a,r=arguments.length,i=r<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,o):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,o,n);else for(var s=e.length-1;s>=0;s--)(a=e[s])&&(i=(r<3?a(i):r>3?a(t,o,i):a(t,o))||i);return r>3&&i&&Object.defineProperty(t,o,i),i};import{html,LitElement,nothing}from"lit";import{customElement,property,query}from"lit/decorators.js";import{until}from"lit/directives/until.js";import{lll}from"@typo3/core/lit-helper.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Persistent from"@typo3/backend/storage/persistent.js";import{ModuleUtility}from"@typo3/backend/module.js";import ContextMenu from"@typo3/backend/context-menu.js";import{PageTree}from"@typo3/backend/tree/page-tree.js";import{TreeNodeCommandEnum,TreeNodePositionEnum}from"@typo3/backend/tree/tree-node.js";import{TreeToolbar}from"@typo3/backend/tree/tree-toolbar.js";import{TreeModuleState}from"@typo3/backend/tree/tree-module-state.js";import Modal from"@typo3/backend/modal.js";import Severity from"@typo3/backend/severity.js";import{ModuleStateStorage}from"@typo3/backend/storage/module-state-storage.js";import{DataTransferTypes}from"@typo3/backend/enum/data-transfer-types.js";export const navigationComponentName="typo3-backend-navigation-component-pagetree";let EditablePageTree=class extends PageTree{constructor(){super(...arguments),this.allowNodeEdit=!0,this.allowNodeDrag=!0,this.allowNodeSorting=!0}sendChangeCommand(e){let t="",o="0";if(e.target)if(o=e.target.identifier,e.position===TreeNodePositionEnum.BEFORE){const t=this.getPreviousNode(e.target);o=(t.depth===e.target.depth?"-":"")+t.identifier}else e.position===TreeNodePositionEnum.AFTER&&(o="-"+o);if(e.command===TreeNodeCommandEnum.NEW){const n=e;t="&data[pages]["+e.node.identifier+"][pid]="+encodeURIComponent(o)+"&data[pages]["+e.node.identifier+"][title]="+encodeURIComponent(n.title)+"&data[pages]["+e.node.identifier+"][doktype]="+encodeURIComponent(n.doktype)}else if(e.command===TreeNodeCommandEnum.EDIT)t="&data[pages]["+e.node.identifier+"][title]="+encodeURIComponent(e.title);else if(e.command===TreeNodeCommandEnum.DELETE){const o=ModuleStateStorage.current("web");e.node.identifier===o.identifier&&this.selectFirstNode(),t="&cmd[pages]["+e.node.identifier+"][delete]=1"}else t="cmd[pages]["+e.node.identifier+"]["+e.command+"]="+o;this.requestTreeUpdate(t).then((t=>{if(t&&t.hasErrors)this.errorNotification(t.messages);else if(e.command===TreeNodeCommandEnum.NEW){const t=this.getParentNode(e.node);t.loaded=!1,this.loadChildren(t)}else this.refreshOrFilterTree()}))}initializeDragForNode(){throw new Error("unused")}async handleNodeEdit(e,t){if(e.__loading=!0,e.identifier.startsWith("NEW")){const o=this.getPreviousNode(e),n=e.depth===o.depth?TreeNodePositionEnum.AFTER:TreeNodePositionEnum.INSIDE,a={command:TreeNodeCommandEnum.NEW,node:e,title:t,position:n,target:o,doktype:e.doktype};await this.sendChangeCommand(a)}else{const o={command:TreeNodeCommandEnum.EDIT,node:e,title:t};await this.sendChangeCommand(o)}e.__loading=!1}createDataTransferItemsFromNode(e){return[{type:DataTransferTypes.treenode,data:this.getNodeTreeIdentifier(e)},{type:DataTransferTypes.pages,data:JSON.stringify({records:[{identifier:e.identifier,tablename:"pages"}]})}]}async handleNodeAdd(e,t,o){this.updateComplete.then((()=>{this.editNode(e)}))}handleNodeDelete(e){const t={node:e,command:TreeNodeCommandEnum.DELETE};if(this.settings.displayDeleteConfirmation){Modal.confirm(TYPO3.lang["mess.delete.title"],TYPO3.lang["mess.delete"].replace("%s",t.node.name),Severity.warning,[{text:TYPO3.lang["labels.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3.lang.delete||"Delete",btnClass:"btn-warning",name:"delete"}]).addEventListener("button.clicked",(e=>{"delete"===e.target.name&&this.sendChangeCommand(t),Modal.dismiss()}))}else this.sendChangeCommand(t)}handleNodeMove(e,t,o){const n={node:e,target:t,position:o,command:TreeNodeCommandEnum.MOVE};let a="";switch(o){case TreeNodePositionEnum.BEFORE:a=TYPO3.lang["mess.move_before"];break;case TreeNodePositionEnum.AFTER:a=TYPO3.lang["mess.move_after"];break;default:a=TYPO3.lang["mess.move_into"]}a=a.replace("%s",e.name).replace("%s",t.name);const r=Modal.confirm(TYPO3.lang.move_page,a,Severity.warning,[{text:TYPO3.lang["labels.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3.lang["cm.copy"]||"Copy",btnClass:"btn-warning",name:"copy"},{text:TYPO3.lang["labels.move"]||"Move",btnClass:"btn-warning",name:"move"}]);r.addEventListener("button.clicked",(e=>{const t=e.target;"move"===t.name?(n.command=TreeNodeCommandEnum.MOVE,this.sendChangeCommand(n)):"copy"===t.name&&(n.command=TreeNodeCommandEnum.COPY,this.sendChangeCommand(n)),r.hideModal()}))}requestTreeUpdate(e){return new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process).post(e,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then((e=>e.resolve())).catch((e=>{this.errorNotification(e),this.loadData()}))}};EditablePageTree=__decorate([customElement("typo3-backend-navigation-component-pagetree-tree")],EditablePageTree);export{EditablePageTree};let PageTreeNavigationComponent=class extends(TreeModuleState(LitElement)){constructor(){super(...arguments),this.mountPointPath=null,this.moduleStateType="web",this.configuration=null,this.refresh=()=>{this.tree.refreshOrFilterTree()},this.setMountPoint=e=>{this.setTemporaryMountPoint(e.detail.pageId)},this.selectFirstNode=()=>{this.tree.selectFirstNode()},this.loadContent=e=>{const t=e.detail.node;if(!t?.checked)return;if(ModuleStateStorage.updateWithTreeIdentifier("web",t.identifier,t.__treeIdentifier),!1===e.detail.propagate)return;const o=top.TYPO3.ModuleMenu.App;let n=ModuleUtility.getFromName(o.getCurrentModule()).link;n+=n.includes("?")?"&":"?",top.TYPO3.Backend.ContentContainer.setUrl(n+"id="+t.identifier)},this.showContextMenu=e=>{const t=e.detail.node;t&&ContextMenu.show(t.recordType,parseInt(t.identifier,10),"tree","","",this.tree.getElementFromNode(t),e.detail.originalEvent)}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:pagetree:refresh",this.refresh),document.addEventListener("typo3:pagetree:mountPoint",this.setMountPoint),document.addEventListener("typo3:pagetree:selectFirstNode",this.selectFirstNode)}disconnectedCallback(){document.removeEventListener("typo3:pagetree:refresh",this.refresh),document.removeEventListener("typo3:pagetree:mountPoint",this.setMountPoint),document.removeEventListener("typo3:pagetree:selectFirstNode",this.selectFirstNode),super.disconnectedCallback()}createRenderRoot(){return this}render(){return html`
      <div id="typo3-pagetree" class="tree">
      ${until(this.renderTree(),"")}
      </div>
    `}getConfiguration(){if(null!==this.configuration)return Promise.resolve(this.configuration);const e=top.TYPO3.settings.ajaxUrls.page_tree_configuration;return new AjaxRequest(e).get().then((async e=>{const t=await e.resolve("json");return this.configuration=t,this.mountPointPath=t.temporaryMountPoint||null,t}))}async renderTree(){const e=await this.getConfiguration();return html`
      <typo3-backend-navigation-component-pagetree-toolbar id="typo3-pagetree-toolbar" .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
      <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
        ${this.renderMountPoint()}
        <typo3-backend-navigation-component-pagetree-tree
            id="typo3-pagetree-tree"
            class="tree-wrapper"
            .setup=${e}
            @tree:initialized=${()=>{this.toolbar.tree=this.tree,this.fetchActiveNodeIfMissing()}}
            @typo3:tree:node-selected=${this.loadContent}
            @typo3:tree:node-context=${this.showContextMenu}
            @typo3:tree:nodes-prepared=${this.selectActiveNodeInLoadedNodes}
        ></typo3-backend-navigation-component-pagetree-tree>
      </div>
    `}unsetTemporaryMountPoint(){Persistent.unset("pageTree_temporaryMountPoint").then((()=>{this.mountPointPath=null}))}renderMountPoint(){return null===this.mountPointPath?nothing:html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${()=>this.unsetTemporaryMountPoint()}" title="${lll("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `}setTemporaryMountPoint(e){new AjaxRequest(this.configuration.setTemporaryMountPointUrl).post("pid="+e,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then((e=>e.resolve())).then((e=>{e&&e.hasErrors?(this.tree.errorNotification(e.message),this.tree.loadData()):this.mountPointPath=e.mountPointPath})).catch((e=>{this.tree.errorNotification(e),this.tree.loadData()}))}};__decorate([property({type:String})],PageTreeNavigationComponent.prototype,"mountPointPath",void 0),__decorate([query(".tree-wrapper")],PageTreeNavigationComponent.prototype,"tree",void 0),__decorate([query("typo3-backend-navigation-component-pagetree-toolbar")],PageTreeNavigationComponent.prototype,"toolbar",void 0),PageTreeNavigationComponent=__decorate([customElement("typo3-backend-navigation-component-pagetree")],PageTreeNavigationComponent);export{PageTreeNavigationComponent};let PageTreeToolbar=class extends TreeToolbar{constructor(){super(...arguments),this.tree=null}render(){return html`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${lll("labels.label.searchString")}
              </label>
              <input type="search" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll("tree.searchTermInfo")}">
          </div>
        </div>
        <div class="tree-toolbar__submenu">
          ${this.tree?.settings?.doktypes?.length?this.tree.settings.doktypes.map((e=>html`
                <div
                  class="tree-toolbar__menuitem tree-toolbar__drag-node"
                  draggable="true"
                  data-tree-icon="${e.icon}"
                  data-node-type="${e.nodeType}"
                  @dragstart="${t=>{this.handleDragStart(t,e)}}"
                >
                  <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
                </div>
              `)):""}
          <button
            type="button"
            class="tree-toolbar__menuitem dropdown-toggle dropdown-toggle-no-chevron float-end"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${()=>this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.refresh")}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${e=>this.collapseAll(e)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.collapse")}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `}handleDragStart(e,t){const o={__hidden:!1,__expanded:!1,__indeterminate:!1,__loading:!1,__processed:!1,__treeDragAction:"",__treeIdentifier:"",__treeParents:[""],__parents:[""],__x:0,__y:0,deletable:!1,depth:0,editable:!0,hasChildren:!1,icon:t.icon,overlayIcon:"",identifier:"NEW"+Math.floor(1e9*Math.random()).toString(16),loaded:!1,name:"",note:"",parentIdentifier:"",prefix:"",recordType:"pages",suffix:"",tooltip:"",type:"PageTreeItem",doktype:t.nodeType,statusInformation:[],labels:[]};this.tree.draggingNode=o,this.tree.nodeDragMode=TreeNodeCommandEnum.NEW,e.dataTransfer.clearData();const n={statusIconIdentifier:this.tree.getNodeDragStatusIcon(),tooltipIconIdentifier:t.icon,tooltipLabel:t.title};e.dataTransfer.setData(DataTransferTypes.dragTooltip,JSON.stringify(n)),e.dataTransfer.setData(DataTransferTypes.newTreenode,JSON.stringify(o)),e.dataTransfer.effectAllowed="move"}};__decorate([property({type:EditablePageTree})],PageTreeToolbar.prototype,"tree",void 0),PageTreeToolbar=__decorate([customElement("typo3-backend-navigation-component-pagetree-toolbar")],PageTreeToolbar);