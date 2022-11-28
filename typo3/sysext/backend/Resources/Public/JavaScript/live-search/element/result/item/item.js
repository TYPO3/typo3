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
var __decorate=function(e,t,r,o){var i,c=arguments.length,n=c<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(n=(c<3?i(n):c>3?i(t,r,n):i(t,r))||n);return c>3&&n&&Object.defineProperty(t,r,n),n};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let Item=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-container"),super.connectedCallback(),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("focus",(e=>{const t=e.target;t.parentElement.querySelector(".active")?.classList.remove("active"),t.classList.add("active")}))}createRenderRoot(){return this}render(){return html`<div class="livesearch-expand-action" @click="${e=>{e.stopPropagation(),this.focus()}}"><typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon></div>`}};__decorate([property({type:Object,attribute:!1})],Item.prototype,"resultItem",void 0),Item=__decorate([customElement("typo3-backend-live-search-result-item")],Item);export{Item};