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
var __decorate=function(e,t,r,n){var i,o=arguments.length,s=o<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,n);else for(var l=e.length-1;l>=0;l--)(i=e[l])&&(s=(o<3?i(s):o>3?i(t,r,s):i(t,r))||s);return o>3&&s&&Object.defineProperty(t,r,s),s};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import"@typo3/backend/live-search/element/result/item/item.js";import"@typo3/backend/live-search/element/provider/default-result-item.js";export const componentName="typo3-backend-live-search-result-item-container";let ItemContainer=class extends LitElement{constructor(){super(...arguments),this.results=null,this.renderers={}}createRenderRoot(){return this}render(){return html`<typo3-backend-live-search-result-list>
      ${this.results.map((e=>this.renderResultItem(e)))}
    </typo3-backend-live-search-result-list>`}renderResultItem(e){let t;return t="function"==typeof this.renderers[e.provider]?this.renderers[e.provider](e):html`<typo3-backend-live-search-result-item-default
        title="${e.typeLabel}: ${e.itemTitle}"
        .icon="${e.icon}"
        .itemTitle="${e.itemTitle}"
        .typeLabel="${e.typeLabel}"
        .extraData="${e.extraData}">
      </typo3-backend-live-search-result-item-default>`,html`<typo3-backend-live-search-result-item
      tabindex="1"
      .resultItem="${e}"
      @click="${()=>this.invokeAction(e,e.actions[0])}"
      @focus="${()=>this.requestActions(e)}">
      ${t}
    </typo3-backend-live-search-result-item>`}requestActions(e){this.parentElement.dispatchEvent(new CustomEvent("livesearch:request-actions",{detail:{resultItem:e}}))}invokeAction(e,t){this.parentElement.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:t}}))}};__decorate([property({type:Object,attribute:!1})],ItemContainer.prototype,"results",void 0),__decorate([property({type:Object,attribute:!1})],ItemContainer.prototype,"renderers",void 0),ItemContainer=__decorate([customElement(componentName)],ItemContainer);export{ItemContainer};let ResultList=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemDetailContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-detail-container"),super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown),this.addEventListener("keyup",this.handleKeyUp)}render(){return html`<slot></slot>`}handleKeyDown(e){if(!["ArrowDown","ArrowUp","ArrowRight"].includes(e.key))return;if("typo3-backend-live-search-result-item"!==document.activeElement.tagName.toLowerCase())return;let t;e.preventDefault(),"ArrowDown"===e.key?t=document.activeElement.nextElementSibling:"ArrowUp"===e.key?(t=document.activeElement.previousElementSibling,null===t&&(t=document.querySelector("typo3-backend-live-search").querySelector('input[type="search"]'))):"ArrowRight"===e.key&&(t=this.resultItemDetailContainer.querySelector("typo3-backend-live-search-result-item-action")),null!==t&&t.focus()}handleKeyUp(e){if(!["Enter"," "].includes(e.key))return;e.preventDefault();const t=e.target.resultItem;this.invokeAction(t)}invokeAction(e){this.parentContainer.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:e.actions[0]}}))}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-live-search-result-list")],ResultList);export{ResultList};