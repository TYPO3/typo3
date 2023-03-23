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
var __decorate=function(e,t,r,i){var o,n=arguments.length,l=n<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,r,i);else for(var a=e.length-1;a>=0;a--)(o=e[a])&&(l=(n<3?o(l):n>3?o(t,r,l):o(t,r))||l);return n>3&&l&&Object.defineProperty(t,r,l),l};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import"@typo3/backend/live-search/element/result/item/action/action-container.js";export const componentName="typo3-backend-live-search-result-item-detail-container";let ResultDetailContainer=class extends LitElement{constructor(){super(...arguments),this.resultItem=null}createRenderRoot(){return this}render(){return null===this.resultItem?nothing:html`
      <div class="livesearch-detail-preamble">
        <typo3-backend-icon identifier="${this.resultItem.icon.identifier}" overlay="${this.resultItem.icon.overlay}" size="large"></typo3-backend-icon>
        <h3>${this.resultItem.itemTitle}</h3>
        <p class="livesearch-detail-preamble-type">${this.resultItem.typeLabel}</p>
      </div>
      <typo3-backend-live-search-result-item-action-container .resultItem="${this.resultItem}"></typo3-backend-live-search-result-item-action-container>
    `}};__decorate([property({type:Object,attribute:!1})],ResultDetailContainer.prototype,"resultItem",void 0),ResultDetailContainer=__decorate([customElement("typo3-backend-live-search-result-item-detail-container")],ResultDetailContainer);export{ResultDetailContainer};