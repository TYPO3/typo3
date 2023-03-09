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
var __decorate=function(e,t,o,i){var r,a=arguments.length,n=a<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,o):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,o,i);else for(var s=e.length-1;s>=0;s--)(r=e[s])&&(n=(a<3?r(n):a>3?r(t,o,n):r(t,o))||n);return a>3&&n&&Object.defineProperty(t,o,n),n};import{customElement,property}from"lit/decorators.js";import{html,css,LitElement,nothing}from"lit";import Modal from"@typo3/backend/modal.js";import"@typo3/backend/element/icon-element.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{lll}from"@typo3/core/lit-helper.js";import Notification from"@typo3/backend/notification.js";import Viewport from"@typo3/backend/viewport.js";import RegularEvent from"@typo3/core/event/regular-event.js";class Item{constructor(e,t,o,i,r,a,n,s){this.identifier=e,this.label=t,this.description=o,this.icon=i,this.url=r,this.requestType=a,this.defaultValues=n,this.saveAndClose=s,this.visible=!0}static fromData(e){return new Item(e.identifier,e.label,e.description,e.icon,e.url,e.requestType??"location",e.defaultValues??[],e.saveAndClose??!1)}reset(){this.visible=!0}}class Category{constructor(e,t,o){this.identifier=e,this.label=t,this.items=o,this.disabled=!1}static fromData(e){return new Category(e.identifier,e.label,e.items.map((e=>Item.fromData(e))))}reset(){this.disabled=!1,this.items.forEach((e=>{e.reset()}))}activeItems(){return this.items.filter((e=>e.visible))??[]}}class Categories{constructor(e){this.items=e}static fromData(e){return new Categories(Object.values(e).map((e=>Category.fromData(e))))}reset(){this.items.forEach((e=>{e.reset()}))}categoriesWithItems(){return this.items.filter((e=>e.activeItems().length>0))??[]}}let NewContentElementWizard=class extends LitElement{constructor(){super(),this.categories=new Categories([]),this.selectedCategory=null,this.searchTerm="",this.messages=[],this.toggleMenu=!1}firstUpdated(){const e=document.createElement("link");e.setAttribute("rel","stylesheet"),e.setAttribute("href",TYPO3.settings.cssUrls.backend),this.shadowRoot.appendChild(e);this.renderRoot.querySelector('input[name="search"]').focus(),this.selectAvailableCategory()}selectAvailableCategory(){0===this.categories.categoriesWithItems().filter((e=>e===this.selectedCategory)).length&&(this.selectedCategory=this.categories.categoriesWithItems()[0]??null),this.messages=[],null===this.selectedCategory&&(this.messages=[{message:lll("newContentElement.filter.noResults"),severity:"info"}])}filter(e){this.searchTerm=e,this.categories.reset(),this.categories.items.forEach((e=>{const t=e.label.trim().replace(/\s+/g," ");!(""!==this.searchTerm&&!RegExp(this.searchTerm,"i").test(t))||e.items.forEach((e=>{const t=e.label.trim().replace(/\s+/g," ")+e.description.trim().replace(/\s+/g," ");e.visible=!(""!==this.searchTerm&&!RegExp(this.searchTerm,"i").test(t))})),e.disabled=0===e.items.filter((e=>e.visible)).length})),this.selectAvailableCategory()}render(){return html`
      <div class="element">
        ${this.renderFilter()}
        ${this.renderMessages()}
        ${null===this.selectedCategory?nothing:html`
        <div class="main">
          <div class="navigation">
            ${this.renderNavigationToggle()}
            ${this.renderNavigationList()}
          </div>
          <div class="content">
            ${this.renderCategories()}
          </div>
        </div>
      `}
      </div>
    `}renderFilter(){return html`
      <form class="filter" @submit="${e=>e.preventDefault()}">
        <input
          name="search"
          type="search"
          autocomplete="off"
          class="form-control"
          .value="${this.searchTerm}"
          @input="${e=>{this.filter(e.target.value)}}"
          @keydown="${e=>{"Escape"===e.code&&(e.stopImmediatePropagation(),this.filter(""))}}"
          placeholder="${lll("newContentElement.filter.placeholder")}"
        />
      </form>
    `}renderMessages(){return html`${this.messages.length>0?html`<div class="messages">${this.messages.map((e=>html`<div class="alert alert-${e.severity}" role="alert">${e.message}</div>`))}</div>`:nothing}`}renderNavigationToggle(){return html`
        <button
          class="navigation-toggle btn btn-light"
          @click="${()=>{this.toggleMenu=!this.toggleMenu}}"
        >
          ${this.selectedCategory.label}
          <typo3-backend-icon identifier="actions-chevron-${!0===this.toggleMenu?"up":"down"}" size="small"></typo3-backend-icon>
        </button>
      `}renderNavigationList(){return html`
      <div class="navigation-list${!0===this.toggleMenu?" show":""}" role="tablist">
    ${this.categories.items.map((e=>html`
        <button
          class="navigation-item${this.selectedCategory===e?" active":""}"
          ?disabled="${e.disabled}"
          @click="${()=>{this.selectedCategory=e,this.toggleMenu=!1}}"
        >
          <span class="navigation-item-label">${e.label}</span>
          <span class="navigation-item-count">${e.activeItems().length}</span>
        </button>
      `))}
      </div>`}renderCategories(){return html`
      <div class="elementwizard-categories">
  ${this.categories.items.map((e=>this.renderCategory(e)))}
      </div>
    `}renderCategory(e){return html`${this.selectedCategory===e?html`
        <div class="item-list">
          ${e.items.map((e=>this.renderCategoryButton(e)))}
        </div>`:nothing}`}renderCategoryButton(e){return html`${e.visible?html`
      <button
        type="button"
        class="item"
        data-identifier="${e.identifier}"
        @click="${t=>{t.preventDefault(),this.handleItemClick(e)}}"
      >
        <div class="item-icon">
          <typo3-backend-icon identifier="${e.icon}" size="medium"></typo3-backend-icon>
        </div>
        <div class="item-body">
          <div class="item-body-label">${e.label}</div>
          <div class="item-body-description">${e.description}</div>
        </div>
      </button>
      `:nothing}`}handleItemClick(e){if(""!==e.url.trim())return"location"===e.requestType?(Viewport.ContentContainer.setUrl(e.url),void Modal.dismiss()):void("ajax"===e.requestType&&new AjaxRequest(e.url).post({defVals:e.defaultValues,saveAndClose:e.saveAndClose?"1":"0"}).then((async e=>{const t=document.createRange().createContextualFragment(await e.resolve());Modal.currentModal.addEventListener("modal-updated",(()=>{new RegularEvent("click",((e,t)=>{e.preventDefault();const o=t.dataset.target;o&&(Viewport.ContentContainer.setUrl(o),Modal.dismiss())})).delegateTo(Modal.currentModal,"button[data-target]")})),Modal.currentModal.setContent(t)})).catch((()=>{Notification.error("Could not load module data")})))}};NewContentElementWizard.styles=[css`
      :host {
        display: block;
        container-type: inline-size;
      }

      .element {
        display: flex;
        flex-direction: column;
        gap: var(--typo3-spacing);
        font-size: var(--typo3-component-font-size);
        line-height: var(--typo3-component-line-height);
      }

      .main {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: calc(var(--typo3-spacing) * 2);
      }

      @container (min-width: 500px) {
        .main {
            flex-direction: row;
        }
      }

      .main > * {
        flex-grow: 1;
      }

      .navigation {
        position: relative;
        flex-shrink: 0;
      }

      @container (min-width: 500px) {
        .navigation {
            flex-grow: 0;
            width: 200px;
        }
      }

      @container (min-width: 500px) {
        .navigation-toggle {
            display: none !important;
        }
      }

      .navigation-list {
        display: none;
        flex-direction: column;
        gap: 2px;
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .navigation-list.show {
        display: flex;
      }

      @container (max-width: 499px) {
        .navigation-list {
          z-index: 1;
          position: absolute;
          padding: var(--typo3-component-border-width);
          background: var(--typo3-component-bg);
          border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
          border-radius: var(--typo3-component-border-radius);
          box-shadow: var(--typo3-component-box-shadow);
        }
      }

      @container (min-width: 500px) {
        .navigation-list {
            display: flex;
        }
      }

      .navigation-item {
        cursor: pointer;
        align-items: center;
        display: flex;
        width: 100%;
        gap: calc(var(--typo3-spacing) / 2);
        text-align: start;
        color: inherit;
        background: transparent;
        border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
        border-radius: var(--typo3-component-border-radius);
        padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
      }

      @container (max-width: 499px) {
        .navigation-item {
          border-radius: calc(var(--typo3-component-border-radius) - var(--typo3-component-border-width));
        }
      }

      .navigation-item:hover {
        color: var(--typo3-component-hover-color);
        background: var(--typo3-component-hover-bg);
        border-color: var(--typo3-component-hover-border-color);
      }

      .navigation-item:focus {
        outline: none;
        color: var(--typo3-component-focus-color);
        background: var(--typo3-component-focus-bg);
        border-color: var(--typo3-component-focus-border-color);
      }

      .navigation-item.active {
        color: var(--typo3-component-active-color);
        background: var(--typo3-component-active-bg);
        border-color: var(--typo3-component-active-border-color);
      }

      .navigation-item:disabled {
        cursor: not-allowed;
        color: var(--typo3-component-disabled-color);
        background: var(--typo3-component-disabled-bg);
        border-color: var(--typo3-component-disabled-border-color);
      }

      .navigation-item-label {
        flex-grow: 1;
      }

      .navigation-item-count {
        opacity: .75;
        flex-shrink: 0;
      }

      .content {
        container-type: inline-size;
      }

      .item-list {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: var(--typo3-spacing);
      }

      @container (min-width: 500px) {
        .item-list {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      .item {
        cursor: pointer;
        display: flex;
        gap: calc(var(--typo3-spacing) / 2);
        text-align: start;
        border: var(--typo3-component-border-width) solid transparent;
        border-radius: var(--typo3-component-border-radius);
        padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
        background: transparent;
        color: inherit;
      }

      .item:hover {
        color: var(--typo3-component-hover-color);
        background: var(--typo3-component-hover-bg);
        border-color: var(--typo3-component-hover-border-color);
      }

      .item:focus {
        outline: none;
        color: var(--typo3-component-focus-color);
        background: var(--typo3-component-focus-bg);
        border-color: var(--typo3-component-focus-border-color);
      }

      .item-body-label {
        font-weight: bold;
        margin-bottom: .25rem;
      }

      .item-body-description {
        opacity: .75;
      }
    `],__decorate([property({type:Object,converter:{fromAttribute:e=>{const t=JSON.parse(e);return Categories.fromData(t)}}})],NewContentElementWizard.prototype,"categories",void 0),__decorate([property({type:String,attribute:!1})],NewContentElementWizard.prototype,"selectedCategory",void 0),__decorate([property({type:String,attribute:!1})],NewContentElementWizard.prototype,"searchTerm",void 0),__decorate([property({type:Array,attribute:!1})],NewContentElementWizard.prototype,"messages",void 0),__decorate([property({type:Boolean,attribute:!1})],NewContentElementWizard.prototype,"toggleMenu",void 0),NewContentElementWizard=__decorate([customElement("typo3-backend-new-content-element-wizard")],NewContentElementWizard);export{NewContentElementWizard};