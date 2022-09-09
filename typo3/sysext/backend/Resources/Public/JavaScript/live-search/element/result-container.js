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
var __decorate=function(e,t,l,r){var n,i=arguments.length,s=i<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,l):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,l,r);else for(var o=e.length-1;o>=0;o--)(n=e[o])&&(s=(i<3?n(s):i>3?n(t,l,s):n(t,l))||s);return i>3&&s&&Object.defineProperty(t,l,s),s};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/live-search/element/result-item.js";let ResultContainer=class extends LitElement{constructor(){super(...arguments),this.results=null,this.loading=!1}connectedCallback(){super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown)}createRenderRoot(){return this}render(){let e=null;return this.loading&&(e=html`<div class="d-flex justify-content-center mt-2"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`),null!==this.results&&(e=0===this.results.length?html`<div class="alert alert-info">${lll("liveSearch_listEmptyText")}</div>`:html`${this.results.map((e=>this.renderResultItem(e)))}`),html`<typo3-backend-live-search-result-list>${e}</typo3-backend-live-search-result-list>`}renderResultItem(e){return html`<typo3-backend-live-search-result-item
      tabindex="1"
      editLink="${e.editLink}"
      icon="${JSON.stringify(e.icon)}"
      uid="${e.uid}"
      pid="${e.pid}"
      title="${e.typeLabel}: ${e.title} - uid:${e.uid}"
      itemTitle="${e.title}"
      typeLabel="${e.typeLabel}">
    </typo3-backend-live-search-result-item>`}handleKeyDown(e){if(e.preventDefault(),!["ArrowDown","ArrowUp"].includes(e.key))return;if("typo3-backend-live-search-result-item"!==document.activeElement.tagName.toLowerCase())return;let t;"ArrowDown"===e.key?t=document.activeElement.nextElementSibling:(t=document.activeElement.previousElementSibling,null===t&&(t=document.getElementById("backend-live-search").querySelector('input[type="search"]'))),null!==t&&t.focus()}};__decorate([property({type:Object})],ResultContainer.prototype,"results",void 0),__decorate([property({type:Boolean})],ResultContainer.prototype,"loading",void 0),ResultContainer=__decorate([customElement("typo3-backend-live-search-result-container")],ResultContainer);export{ResultContainer};let ResultList=class extends LitElement{render(){return html`<slot></slot>`}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-live-search-result-list")],ResultList);export{ResultList};