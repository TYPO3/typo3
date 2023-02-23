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
var __decorate=function(e,t,r,o){var c,n=arguments.length,i=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,o);else for(var s=e.length-1;s>=0;s--)(c=e[s])&&(i=(n<3?c(i):n>3?c(t,r,i):c(t,r))||i);return n>3&&i&&Object.defineProperty(t,r,i),i};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let Item=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-container"),super.connectedCallback(),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("focus",this.onFocus)}disconnectedCallback(){this.removeEventListener("focus",this.onFocus),super.disconnectedCallback()}createRenderRoot(){return this}render(){return html`<div class="livesearch-expand-action" @click="${e=>{e.stopPropagation(),this.focus()}}"><typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon></div>`}onFocus(e){const t=e.target;t.parentElement.querySelector(".active")?.classList.remove("active"),t.classList.add("active")}};__decorate([property({type:Object,attribute:!1})],Item.prototype,"resultItem",void 0),Item=__decorate([customElement("typo3-backend-live-search-result-item")],Item);export{Item};