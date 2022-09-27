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
var __decorate=function(e,t,r,s){var n,l=arguments.length,o=l<3?t:null===s?s=Object.getOwnPropertyDescriptor(t,r):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,r,s);else for(var i=e.length-1;i>=0;i--)(n=e[i])&&(o=(l<3?n(o):l>3?n(t,r,o):n(t,r))||o);return l>3&&o&&Object.defineProperty(t,r,o),o};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/form-engine/element/suggest/result-item.js";let ResultContainer=class extends LitElement{constructor(){super(...arguments),this.results=null}connectedCallback(){super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown)}createRenderRoot(){return this}render(){let e;return null!==this.results&&(e=0===this.results.length?html`<div class="alert alert-info">${lll("search.no_records_found")}</div>`:html`${this.results.map((e=>this.renderResultItem(e)))}`),html`<typo3-backend-formengine-suggest-result-list>${e}</typo3-backend-formengine-suggest-result-list>`}renderResultItem(e){return html`<typo3-backend-formengine-suggest-result-item
      tabindex="1"
      icon="${JSON.stringify(e.icon)}"
      uid="${e.uid}"
      table="${e.table}"
      label="${e.label}"
      path="${e.path}">
    </typo3-backend-formengine-suggest-result-item>`}handleKeyDown(e){if(e.preventDefault(),"Escape"===e.key)return this.closest(".t3-form-suggest-container").querySelector('input[type="search"]').focus(),void(this.hidden=!0);if(!["ArrowDown","ArrowUp"].includes(e.key))return;if("typo3-backend-formengine-suggest-result-item"!==document.activeElement.tagName.toLowerCase())return;let t;"ArrowDown"===e.key?t=document.activeElement.nextElementSibling:(t=document.activeElement.previousElementSibling,null===t&&(t=this.closest(".t3-form-suggest-container").querySelector('input[type="search"]'))),null!==t&&t.focus()}};__decorate([property({type:Object})],ResultContainer.prototype,"results",void 0),ResultContainer=__decorate([customElement("typo3-backend-formengine-suggest-result-container")],ResultContainer);let ResultList=class extends LitElement{render(){return html`<slot></slot>`}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-formengine-suggest-result-list")],ResultList);export{ResultList};