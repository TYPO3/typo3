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
var __decorate=function(e,t,r,n){var o,c=arguments.length,l=c<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,r,n);else for(var i=e.length-1;i>=0;i--)(o=e[i])&&(l=(c<3?o(l):c>3?o(t,r,l):o(t,r))||l);return c>3&&l&&Object.defineProperty(t,r,l),l};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let ResultItem=class extends LitElement{connectedCallback(){super.connectedCallback(),this.addEventListener("click",(e=>{e.preventDefault(),this.dispatchItemChosenEvent()})),this.addEventListener("keyup",(e=>{e.preventDefault(),["Enter"," "].includes(e.key)&&this.dispatchItemChosenEvent()}))}createRenderRoot(){return this}render(){return html``}dispatchItemChosenEvent(){document.dispatchEvent(new CustomEvent("live-search:item-chosen",{detail:{callback:()=>{TYPO3.Backend.ContentContainer.setUrl(this.actionUrl)}}}))}};__decorate([property({type:String})],ResultItem.prototype,"provider",void 0),__decorate([property({type:String})],ResultItem.prototype,"actionUrl",void 0),ResultItem=__decorate([customElement("typo3-backend-live-search-result-item")],ResultItem);export{ResultItem};