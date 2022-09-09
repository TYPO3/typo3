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
var __decorate=function(e,t,i,r){var o,l=arguments.length,n=l<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,i,r);else for(var s=e.length-1;s>=0;s--)(o=e[s])&&(n=(l<3?o(n):l>3?o(t,i,n):o(t,i))||n);return l>3&&n&&Object.defineProperty(t,i,n),n};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let ResultItem=class extends LitElement{connectedCallback(){super.connectedCallback(),this.addEventListener("click",(e=>{e.preventDefault(),this.dispatchItemChosenEvent()})),this.addEventListener("keyup",(e=>{e.preventDefault(),["Enter"," "].includes(e.key)&&this.dispatchItemChosenEvent()}))}createRenderRoot(){return this}render(){return html`
      <div class="livesearch-result-item-icon">
        <typo3-backend-icon title="${this.icon.title}" identifier="${this.icon.identifier}" overlay="${this.icon.overlay}" size="small"></typo3-backend-icon>
      </div>
      <div class="livesearch-result-item-title">
        ${this.itemTitle} <small>- uid:${this.uid}</small>
      </div>
      <div class="livesearch-result-item-type">
        ${this.typeLabel}
      </div>
    `}dispatchItemChosenEvent(){document.dispatchEvent(new CustomEvent("live-search:item-chosen",{detail:{callback:()=>{TYPO3.Backend.ContentContainer.setUrl(this.editLink)}}}))}};__decorate([property({type:String})],ResultItem.prototype,"editLink",void 0),__decorate([property({type:Object})],ResultItem.prototype,"icon",void 0),__decorate([property({type:Number})],ResultItem.prototype,"uid",void 0),__decorate([property({type:Number})],ResultItem.prototype,"pid",void 0),__decorate([property({type:String})],ResultItem.prototype,"itemTitle",void 0),__decorate([property({type:String})],ResultItem.prototype,"typeLabel",void 0),ResultItem=__decorate([customElement("typo3-backend-live-search-result-item")],ResultItem);export{ResultItem};