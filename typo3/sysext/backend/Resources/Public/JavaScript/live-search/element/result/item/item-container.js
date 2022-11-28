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
var __decorate=function(e,t,r,n){var l,s=arguments.length,i=s<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,n);else for(var o=e.length-1;o>=0;o--)(l=e[o])&&(i=(s<3?l(i):s>3?l(t,r,i):l(t,r))||i);return s>3&&i&&Object.defineProperty(t,r,i),i};import{customElement,property}from"lit/decorators.js";import{css,html,LitElement}from"lit";import"@typo3/backend/live-search/element/result/item/item.js";import"@typo3/backend/live-search/element/provider/default-result-item.js";export const componentName="typo3-backend-live-search-result-item-container";let ItemContainer=class extends LitElement{constructor(){super(...arguments),this.results=null,this.renderers={}}connectedCallback(){super.connectedCallback(),this.addEventListener("scroll",this.onScroll)}disconnectedCallback(){this.removeEventListener("scroll",this.onScroll),super.disconnectedCallback()}createRenderRoot(){return this}render(){const e={};return this.results.forEach((t=>{t.typeLabel in e?e[t.typeLabel].push(t):e[t.typeLabel]=[t]})),html`<typo3-backend-live-search-result-list>
      ${this.renderGroupedResults(e)}
    </typo3-backend-live-search-result-list>`}renderGroupedResults(e){const t=[];for(let[r,n]of Object.entries(e))t.push(html`<h6 class="livesearch-result-item-group-label">${r}</h6>`),t.push(...n.map((e=>this.renderResultItem(e))));return html`${t}`}renderResultItem(e){let t;return t="function"==typeof this.renderers[e.provider]?this.renderers[e.provider](e):html`<typo3-backend-live-search-result-item-default
        title="${e.typeLabel}: ${e.itemTitle}"
        .icon="${e.icon}"
        .itemTitle="${e.itemTitle}"
        .typeLabel="${e.typeLabel}"
        .extraData="${e.extraData}">
      </typo3-backend-live-search-result-item-default>`,html`<typo3-backend-live-search-result-item
      .resultItem="${e}"
      @click="${()=>this.invokeAction(e,e.actions[0])}"
      @focus="${()=>this.requestActions(e)}">
      ${t}
    </typo3-backend-live-search-result-item>`}requestActions(e){this.parentElement.dispatchEvent(new CustomEvent("livesearch:request-actions",{detail:{resultItem:e}}))}invokeAction(e,t){this.parentElement.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:t}}))}onScroll(e){this.querySelectorAll(".livesearch-result-item-group-label").forEach((t=>{t.classList.toggle("sticky",t.offsetTop<=e.target.scrollTop)}))}};__decorate([property({type:Object,attribute:!1})],ItemContainer.prototype,"results",void 0),__decorate([property({type:Object,attribute:!1})],ItemContainer.prototype,"renderers",void 0),ItemContainer=__decorate([customElement(componentName)],ItemContainer);export{ItemContainer};let ResultList=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemDetailContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-detail-container"),super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown),this.addEventListener("keyup",this.handleKeyUp)}disconnectedCallback(){this.removeEventListener("keydown",this.handleKeyDown),this.removeEventListener("keyup",this.handleKeyUp),super.disconnectedCallback()}render(){return html`<slot></slot>`}handleKeyDown(e){if(!["ArrowDown","ArrowUp","ArrowRight"].includes(e.key))return;const t="typo3-backend-live-search-result-item";if(document.activeElement.tagName.toLowerCase()!==t)return;let r;if(e.preventDefault(),"ArrowDown"===e.key){let e=document.activeElement.nextElementSibling;for(;null!==e&&e.tagName.toLowerCase()!==t;)e=e.nextElementSibling;r=e}else if("ArrowUp"===e.key){let e=document.activeElement.previousElementSibling;for(;null!==e&&e.tagName.toLowerCase()!==t;)e=e.previousElementSibling;r=e,null===r&&(r=document.querySelector("typo3-backend-live-search").querySelector('input[type="search"]'))}else"ArrowRight"===e.key&&(r=this.resultItemDetailContainer.querySelector("typo3-backend-live-search-result-item-action"));null!==r&&r.focus()}handleKeyUp(e){if(!["Enter"," "].includes(e.key))return;e.preventDefault();const t=e.target.resultItem;this.invokeAction(t)}invokeAction(e){this.parentContainer.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:e.actions[0]}}))}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-live-search-result-list")],ResultList);export{ResultList};