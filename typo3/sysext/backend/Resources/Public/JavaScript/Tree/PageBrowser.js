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
var __decorate=this&&this.__decorate||function(e,t,n,i){var o,r=arguments.length,s=r<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,n):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,n,i);else for(var a=e.length-1;a>=0;a--)(o=e[a])&&(s=(r<3?o(s):r>3?o(t,n,s):o(t,n))||s);return r>3&&s&&Object.defineProperty(t,n,s),s},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit","lit/decorators","lit/directives/until","TYPO3/CMS/Core/lit-helper","../PageTree/PageTree","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Recordlist/ElementBrowser","TYPO3/CMS/Recordlist/LinkBrowser","TYPO3/CMS/Backend/Storage/Persistent","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,n,i,o,r,s,a,c,d,l){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageBrowser=void 0,a=__importDefault(a),l=__importDefault(l);let p=class extends s.PageTree{appendTextElement(e){return super.appendTextElement(e).attr("opacity",e=>this.settings.actions.includes("link")?this.isLinkable(e)?1:.5:1)}updateNodeActions(e){const t=super.updateNodeActions(e);if(this.settings.actions.includes("link")){this.fetchIcon("actions-link");const e=this.nodesActionsContainer.selectAll(".node-action").append("g").attr("visibility",e=>this.isLinkable(e)?"visible":"hidden").on("click",(e,t)=>{this.linkItem(t)});this.createIconAreaForAction(e,"actions-link")}else if(this.settings.actions.includes("select")){this.fetchIcon("actions-link");const e=t.append("g").on("click",(e,t)=>{this.selectItem(t)});this.createIconAreaForAction(e,"actions-link")}return t}linkItem(e){d.finalizeFunction("t3://page?uid="+e.identifier)}isLinkable(e){return!1===["199","254","255"].includes(String(e.type))}selectItem(e){c.insertElement(e.itemType,e.identifier,e.name,e.identifier,!0)}};p=__decorate([(0,i.customElement)("typo3-backend-component-page-browser-tree")],p);let u=class extends n.LitElement{constructor(){super(...arguments),this.mountPointPath=null,this.activePageId=0,this.actions=[],this.configuration=null,this.triggerRender=()=>{this.tree.dispatchEvent(new Event("svg-tree:visible"))},this.selectActivePageInTree=e=>{let t=e.detail.nodes;e.detail.nodes=t.map(e=>(parseInt(e.identifier,10)===this.activePageId&&(e.checked=!0),e))},this.toggleExpandState=e=>{const t=e.detail.node;t&&l.default.set("BackendComponents.States.Pagetree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadRecordsOfPage=e=>{const t=e.detail.node;if(!t.checked)return;let n=document.location.href+"&contentOnly=1&expandPage="+t.identifier;new a.default(n).get().then(e=>e.resolve()).then(e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e})},this.setMountPoint=e=>{this.setTemporaryMountPoint(e.detail.pageId)}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:navigation:resized",this.triggerRender),document.addEventListener("typo3:pagetree:mountPoint",this.setMountPoint)}disconnectedCallback(){document.removeEventListener("typo3:navigation:resized",this.triggerRender),document.removeEventListener("typo3:pagetree:mountPoint",this.setMountPoint),super.disconnectedCallback()}firstUpdated(){this.activePageId=parseInt(this.getAttribute("active-page"),10),this.actions=JSON.parse(this.getAttribute("tree-actions"))}createRenderRoot(){return this}getConfiguration(){if(null!==this.configuration)return Promise.resolve(this.configuration);const e=top.TYPO3.settings.ajaxUrls.page_tree_browser_configuration,t=this.hasAttribute("alternative-entry-points")?JSON.parse(this.getAttribute("alternative-entry-points")):[];let n=new a.default(e);return t.length&&(n=n.withQueryArguments("alternativeEntryPoints="+encodeURIComponent(t))),n.get().then(async e=>{const t=await e.resolve("json");return t.actions=this.actions,this.configuration=t,this.mountPointPath=t.temporaryMountPoint||null,t})}render(){return n.html`
      <div class="svg-tree">
        ${(0,o.until)(this.renderTree(),this.renderLoader())}
      </div>
    `}renderTree(){return this.getConfiguration().then(e=>n.html`
          <div>
            <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
            <div class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-component-page-browser-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{this.tree.dispatchEvent(new Event("svg-tree:visible")),this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadRecordsOfPage),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActivePageInTree);this.querySelector("typo3-backend-tree-toolbar").tree=this.tree}}></typo3-backend-component-page-browser-tree>
            </div>
          </div>
          ${this.renderLoader()}
        `)}renderLoader(){return n.html`
      <div class="svg-tree-loader">
        <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
      </div>
    `}unsetTemporaryMountPoint(){this.mountPointPath=null,l.default.unset("pageTree_temporaryMountPoint").then(()=>{this.tree.refreshTree()})}renderMountPoint(){return null===this.mountPointPath?n.html``:n.html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${()=>this.unsetTemporaryMountPoint()}" title="${(0,r.lll)("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `}setTemporaryMountPoint(e){new a.default(this.configuration.setTemporaryMountPointUrl).post("pid="+e,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then(e=>e.resolve()).then(e=>{e&&e.hasErrors?(this.tree.errorNotification(e.message,!0),this.tree.updateVisibleNodes()):(this.mountPointPath=e.mountPointPath,this.tree.refreshOrFilterTree())}).catch(e=>{this.tree.errorNotification(e,!0)})}};__decorate([(0,i.property)({type:String})],u.prototype,"mountPointPath",void 0),__decorate([(0,i.query)(".svg-tree-wrapper")],u.prototype,"tree",void 0),u=__decorate([(0,i.customElement)("typo3-backend-component-page-browser")],u),t.PageBrowser=u}));