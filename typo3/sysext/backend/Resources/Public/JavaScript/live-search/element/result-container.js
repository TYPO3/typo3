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
var __decorate=function(e,t,r,l){var n,o=arguments.length,i=o<3?t:null===l?l=Object.getOwnPropertyDescriptor(t,r):l;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,l);else for(var s=e.length-1;s>=0;s--)(n=e[s])&&(i=(o<3?n(i):o>3?n(t,r,i):n(t,r))||i);return o>3&&i&&Object.defineProperty(t,r,i),i};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/live-search/element/result-item.js";import"@typo3/backend/live-search/element/provider/default-result-item.js";import RegularEvent from"@typo3/core/event/regular-event.js";export const componentName="typo3-backend-live-search-result-container";let ResultContainer=class extends LitElement{constructor(){super(...arguments),this.results=null,this.loading=!1,this.renderers={}}connectedCallback(){super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown),new RegularEvent("live-search:item-chosen",(e=>{e.detail.callback()})).bindTo(document)}createRenderRoot(){return this}render(){let e=null;return this.loading&&(e=html`<div class="d-flex justify-content-center mt-2"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`),null!==this.results&&(e=0===this.results.length?html`<div class="alert alert-info">${lll("liveSearch_listEmptyText")}</div>`:html`${this.results.map((e=>this.renderResultItem(e)))}`),html`<typo3-backend-live-search-result-list>${e}</typo3-backend-live-search-result-list>`}renderResultItem(e){let t;return t="function"==typeof this.renderers[e.provider]?this.renderers[e.provider](e):html`<typo3-backend-live-search-result-item-default
        title="${e.typeLabel}: ${e.itemTitle}"
        .icon="${e.icon}"
        .itemTitle="${e.itemTitle}"
        .typeLabel="${e.typeLabel}"
        .extraData="${e.extraData}">
      </typo3-backend-live-search-result-item-default>`,html`<typo3-backend-live-search-result-item
      tabindex="1"
      provider="${e.provider}"
      actionUrl="${e.actionUrl}">
      ${t}
    </typo3-backend-live-search-result-item>`}handleKeyDown(e){if(e.preventDefault(),!["ArrowDown","ArrowUp"].includes(e.key))return;if("typo3-backend-live-search-result-item"!==document.activeElement.tagName.toLowerCase())return;let t;"ArrowDown"===e.key?t=document.activeElement.nextElementSibling:(t=document.activeElement.previousElementSibling,null===t&&(t=document.getElementById("backend-live-search").querySelector('input[type="search"]'))),null!==t&&t.focus()}};__decorate([property({type:Object,attribute:!1})],ResultContainer.prototype,"results",void 0),__decorate([property({type:Boolean,attribute:!1})],ResultContainer.prototype,"loading",void 0),__decorate([property({type:Object,attribute:!1})],ResultContainer.prototype,"renderers",void 0),ResultContainer=__decorate([customElement(componentName)],ResultContainer);export{ResultContainer};let ResultList=class extends LitElement{render(){return html`<slot></slot>`}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-live-search-result-list")],ResultList);export{ResultList};