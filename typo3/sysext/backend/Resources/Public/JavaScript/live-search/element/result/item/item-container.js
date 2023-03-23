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
var __decorate=function(e,t,r,n){var l,i=arguments.length,s=i<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,n);else for(var o=e.length-1;o>=0;o--)(l=e[o])&&(s=(i<3?l(s):i>3?l(t,r,s):l(t,r))||s);return i>3&&s&&Object.defineProperty(t,r,s),s};import"@typo3/backend/element/spinner-element.js";import LiveSearchConfigurator from"@typo3/backend/live-search/live-search-configurator.js";import{css,html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{until}from"lit/directives/until.js";import"@typo3/backend/live-search/element/provider/default-result-item.js";import"@typo3/backend/live-search/element/result/item/item.js";export const componentName="typo3-backend-live-search-result-item-container";let ItemContainer=class extends LitElement{constructor(){super(...arguments),this.results=null}connectedCallback(){super.connectedCallback(),this.addEventListener("scroll",this.onScroll)}disconnectedCallback(){this.removeEventListener("scroll",this.onScroll),super.disconnectedCallback()}createRenderRoot(){return this}render(){const e={},t=this.results.filter((e=>null!==e));return t.length!==this.results.length&&console.warn('The result set contained "null" values, indicating something went wrong while building the search results. Affected values were removed to no break the user interface.'),t.forEach((t=>{t.typeLabel in e?e[t.typeLabel].push(t):e[t.typeLabel]=[t]})),html`<typo3-backend-live-search-result-list>
      ${this.renderGroupedResults(e)}
    </typo3-backend-live-search-result-list>`}renderGroupedResults(e){const t=[];for(const[r,n]of Object.entries(e)){const e=n.length;t.push(html`<h6 class="livesearch-result-item-group-label">${r} (${e})</h6>`),t.push(...n.map((e=>html`${until(this.renderResultItem(e),html`<typo3-backend-spinner></typo3-backend-spinner>`)}`)))}return html`${t}`}async renderResultItem(e){const t=LiveSearchConfigurator.getRenderers();let r;return void 0!==t[e.provider]?(await import(t[e.provider].module),r=t[e.provider].callback(e)):r=html`<typo3-backend-live-search-result-item-default
        title="${e.typeLabel}: ${e.itemTitle}"
        .icon="${e.icon}"
        .itemTitle="${e.itemTitle}"
        .typeLabel="${e.typeLabel}"
        .extraData="${e.extraData}">
      </typo3-backend-live-search-result-item-default>`,html`<typo3-backend-live-search-result-item
      .resultItem="${e}"
      @click="${()=>this.invokeAction(e,e.actions[0])}"
      @focus="${()=>this.requestActions(e)}">
      ${r}
    </typo3-backend-live-search-result-item>`}requestActions(e){this.parentElement.dispatchEvent(new CustomEvent("livesearch:request-actions",{detail:{resultItem:e}}))}invokeAction(e,t){this.parentElement.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:t}}))}onScroll(e){this.querySelectorAll(".livesearch-result-item-group-label").forEach((t=>{t.classList.toggle("sticky",t.offsetTop<=e.target.scrollTop)}))}};__decorate([property({type:Object,attribute:!1})],ItemContainer.prototype,"results",void 0),ItemContainer=__decorate([customElement("typo3-backend-live-search-result-item-container")],ItemContainer);export{ItemContainer};let ResultList=class extends LitElement{connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search-result-container"),this.resultItemDetailContainer=this.parentContainer.querySelector("typo3-backend-live-search-result-item-detail-container"),super.connectedCallback(),this.addEventListener("keydown",this.handleKeyDown),this.addEventListener("keyup",this.handleKeyUp)}disconnectedCallback(){this.removeEventListener("keydown",this.handleKeyDown),this.removeEventListener("keyup",this.handleKeyUp),super.disconnectedCallback()}render(){return html`<slot></slot>`}handleKeyDown(e){if(!["ArrowDown","ArrowUp","ArrowRight"].includes(e.key))return;const t="typo3-backend-live-search-result-item";if(document.activeElement.tagName.toLowerCase()!==t)return;let r;if(e.preventDefault(),"ArrowDown"===e.key){let e=document.activeElement.nextElementSibling;for(;null!==e&&e.tagName.toLowerCase()!==t;)e=e.nextElementSibling;r=e}else if("ArrowUp"===e.key){let e=document.activeElement.previousElementSibling;for(;null!==e&&e.tagName.toLowerCase()!==t;)e=e.previousElementSibling;r=e,null===r&&(r=document.querySelector("typo3-backend-live-search").querySelector('input[type="search"]'))}else"ArrowRight"===e.key&&(r=this.resultItemDetailContainer.querySelector("typo3-backend-live-search-result-item-action"));null!==r&&r.focus()}handleKeyUp(e){if(!["Enter"," "].includes(e.key))return;e.preventDefault();const t=e.target.resultItem;this.invokeAction(t)}invokeAction(e){this.parentContainer.dispatchEvent(new CustomEvent("livesearch:invoke-action",{detail:{resultItem:e,action:e.actions[0]}}))}};ResultList.styles=css`
    :host {
      display: block;
    }
  `,ResultList=__decorate([customElement("typo3-backend-live-search-result-list")],ResultList);export{ResultList};