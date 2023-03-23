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
var __decorate=function(e,t,n,r){var i,o=arguments.length,c=o<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,n):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(e,t,n,r);else for(var s=e.length-1;s>=0;s--)(i=e[s])&&(c=(o<3?i(c):o>3?i(t,n,c):i(t,n))||c);return o>3&&c&&Object.defineProperty(t,n,c),c};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import"@typo3/backend/live-search/element/result/item/action/action.js";export const componentName="typo3-backend-live-search-result-item-action-container";let ActionContainer=class extends LitElement{constructor(){super(...arguments),this.resultItem=null}createRenderRoot(){return this}render(){return html`<typo3-backend-live-search-result-action-list>
      ${this.resultItem.actions.map((e=>this.renderActionItem(this.resultItem,e)))}
    </typo3-backend-live-search-result-action-list>`}renderActionItem(e,t){return html`<typo3-backend-live-search-result-item-action
      .resultItem="${e}"
      .resultItemAction="${t}"
      @click="${()=>this.invokeAction(this.resultItem,t)}">
    </typo3-backend-live-search-result-item-action>`}invokeAction(e,t){this.closest("typo3-backend-live-search-result-container").dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:t}}))}};__decorate([property({type:Object,attribute:!1})],ActionContainer.prototype,"resultItem",void 0),ActionContainer=__decorate([customElement("typo3-backend-live-search-result-item-action-container")],ActionContainer);export{ActionContainer};let ActionList=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-container"),super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown),this.addEventListener("keyup",this.handleKeyUp)}disconnectedCallback(){this.removeEventListener("keydown",this.handleKeyDown),this.removeEventListener("keyup",this.handleKeyUp),super.disconnectedCallback()}render(){return html`<slot></slot>`}handleKeyDown(e){if(!["ArrowDown","ArrowUp","ArrowLeft"].includes(e.key))return;if("typo3-backend-live-search-result-item-action"!==document.activeElement.tagName.toLowerCase())return;let t;e.preventDefault(),"ArrowDown"===e.key?t=document.activeElement.nextElementSibling:"ArrowUp"===e.key?t=document.activeElement.previousElementSibling:"ArrowLeft"===e.key&&(t=this.resultItemContainer.querySelector("typo3-backend-live-search-result-item.active")),null!==t&&t.focus()}handleKeyUp(e){if(!["Enter"," "].includes(e.key))return;e.preventDefault();const t=e.target;this.parentContainer.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:t.resultItem,action:t.resultItemAction}}))}};ActionList.styles=css`
    :host {
      display: block;
    }
  `,ActionList=__decorate([customElement("typo3-backend-live-search-result-action-list")],ActionList);export{ActionList};