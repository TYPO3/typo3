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
var __decorate=function(e,t,n,i){var r,o=arguments.length,s=o<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,n):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,n,i);else for(var a=e.length-1;a>=0;a--)(r=e[a])&&(s=(o<3?r(s):o>3?r(t,n,s):r(t,n))||s);return o>3&&s&&Object.defineProperty(t,n,s),s};import{html,LitElement}from"lit";import{customElement,property,query}from"lit/decorators.js";import{until}from"lit/directives/until.js";import{lll}from"@typo3/core/lit-helper.js";import{PageTree}from"@typo3/backend/page-tree/page-tree.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ElementBrowser from"@typo3/recordlist/element-browser.js";import LinkBrowser from"@typo3/recordlist/link-browser.js";import"@typo3/backend/element/icon-element.js";import Persistent from"@typo3/backend/storage/persistent.js";const componentName="typo3-backend-component-page-browser";let PageBrowserTree=class extends PageTree{appendTextElement(e){return super.appendTextElement(e).attr("opacity",e=>this.settings.actions.includes("link")?this.isLinkable(e)?1:.5:1)}updateNodeActions(e){const t=super.updateNodeActions(e);if(this.settings.actions.includes("link")){this.fetchIcon("actions-link");const e=this.nodesActionsContainer.selectAll(".node-action").append("g").attr("visibility",e=>this.isLinkable(e)?"visible":"hidden").on("click",(e,t)=>{this.linkItem(t)});this.createIconAreaForAction(e,"actions-link")}else if(this.settings.actions.includes("select")){this.fetchIcon("actions-link");const e=t.append("g").on("click",(e,t)=>{this.selectItem(t)});this.createIconAreaForAction(e,"actions-link")}return t}linkItem(e){LinkBrowser.finalizeFunction("t3://page?uid="+e.identifier)}isLinkable(e){return!1===["199","254","255"].includes(String(e.type))}selectItem(e){ElementBrowser.insertElement(e.itemType,e.identifier,e.name,e.identifier,!0)}};PageBrowserTree=__decorate([customElement("typo3-backend-component-page-browser-tree")],PageBrowserTree);let PageBrowser=class extends LitElement{constructor(){super(...arguments),this.mountPointPath=null,this.activePageId=0,this.actions=[],this.configuration=null,this.triggerRender=()=>{this.tree.dispatchEvent(new Event("svg-tree:visible"))},this.selectActivePageInTree=e=>{let t=e.detail.nodes;e.detail.nodes=t.map(e=>(parseInt(e.identifier,10)===this.activePageId&&(e.checked=!0),e))},this.toggleExpandState=e=>{const t=e.detail.node;t&&Persistent.set("BackendComponents.States.Pagetree.stateHash."+t.stateIdentifier,t.expanded?"1":"0")},this.loadRecordsOfPage=e=>{const t=e.detail.node;if(!t.checked)return;let n=document.location.href+"&contentOnly=1&expandPage="+t.identifier;new AjaxRequest(n).get().then(e=>e.resolve()).then(e=>{document.querySelector(".element-browser-main-content .element-browser-body").innerHTML=e})},this.setMountPoint=e=>{this.setTemporaryMountPoint(e.detail.pageId)}}connectedCallback(){super.connectedCallback(),document.addEventListener("typo3:navigation:resized",this.triggerRender),document.addEventListener("typo3:pagetree:mountPoint",this.setMountPoint)}disconnectedCallback(){document.removeEventListener("typo3:navigation:resized",this.triggerRender),document.removeEventListener("typo3:pagetree:mountPoint",this.setMountPoint),super.disconnectedCallback()}firstUpdated(){this.activePageId=parseInt(this.getAttribute("active-page"),10),this.actions=JSON.parse(this.getAttribute("tree-actions"))}createRenderRoot(){return this}getConfiguration(){if(null!==this.configuration)return Promise.resolve(this.configuration);const e=top.TYPO3.settings.ajaxUrls.page_tree_browser_configuration,t=this.hasAttribute("alternative-entry-points")?JSON.parse(this.getAttribute("alternative-entry-points")):[];let n=new AjaxRequest(e);return t.length&&(n=n.withQueryArguments("alternativeEntryPoints="+encodeURIComponent(t))),n.get().then(async e=>{const t=await e.resolve("json");return t.actions=this.actions,this.configuration=t,this.mountPointPath=t.temporaryMountPoint||null,t})}render(){return html`
      <div class="svg-tree">
        ${until(this.renderTree(),this.renderLoader())}
      </div>
    `}renderTree(){return this.getConfiguration().then(e=>html`
          <div>
            <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
            <div class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-component-page-browser-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{this.tree.dispatchEvent(new Event("svg-tree:visible")),this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadRecordsOfPage),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActivePageInTree);this.querySelector("typo3-backend-tree-toolbar").tree=this.tree}}></typo3-backend-component-page-browser-tree>
            </div>
          </div>
          ${this.renderLoader()}
        `)}renderLoader(){return html`
      <div class="svg-tree-loader">
        <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
      </div>
    `}unsetTemporaryMountPoint(){this.mountPointPath=null,Persistent.unset("pageTree_temporaryMountPoint").then(()=>{this.tree.refreshTree()})}renderMountPoint(){return null===this.mountPointPath?html``:html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${()=>this.unsetTemporaryMountPoint()}" title="${lll("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `}setTemporaryMountPoint(e){new AjaxRequest(this.configuration.setTemporaryMountPointUrl).post("pid="+e,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then(e=>e.resolve()).then(e=>{e&&e.hasErrors?(this.tree.errorNotification(e.message,!0),this.tree.updateVisibleNodes()):(this.mountPointPath=e.mountPointPath,this.tree.refreshOrFilterTree())}).catch(e=>{this.tree.errorNotification(e,!0)})}};__decorate([property({type:String})],PageBrowser.prototype,"mountPointPath",void 0),__decorate([query(".svg-tree-wrapper")],PageBrowser.prototype,"tree",void 0),PageBrowser=__decorate([customElement(componentName)],PageBrowser);export{PageBrowser};