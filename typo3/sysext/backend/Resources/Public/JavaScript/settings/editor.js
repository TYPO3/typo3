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
var __decorate=function(e,t,i,r){var s,o=arguments.length,n=o<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,i,r);else for(var a=e.length-1;a>=0;a--)(s=e[a])&&(n=(o<3?s(n):o>3?s(t,i,n):s(t,i))||n);return o>3&&n&&Object.defineProperty(t,i,n),n};import{html,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import{live}from"lit/directives/live.js";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{copyToClipboard}from"@typo3/backend/copy-to-clipboard.js";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/settings/editor/editable-setting.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/settings/type/bool.js";import"@typo3/backend/settings/type/int.js";import"@typo3/backend/settings/type/number.js";import"@typo3/backend/settings/type/string.js";import"@typo3/backend/settings/type/stringlist.js";let SettingsEditorElement=class extends LitElement{constructor(){super(...arguments),this.customFormData={},this.debug=!1,this.searchTerm="",this.activeCategory="",this.visibleCategories={},this.observer=null}createRenderRoot(){return this}firstUpdated(){this.observer=new IntersectionObserver((e=>{e.forEach((e=>{const t=e.target.dataset.key;this.visibleCategories[t]=e.isIntersecting}));const t=e=>e.reduce(((e,i)=>[...e,i.key,...t(i.categories)]),[]),i=t(this.categories).filter((e=>this.visibleCategories[e]))[0]||"";i&&(this.activeCategory=i)}),{root:document.querySelector(".module"),threshold:.1,rootMargin:`-${getComputedStyle(document.querySelector(".module-docheader")).getPropertyValue("min-height")} 0px 0px 0px`})}updated(){[...this.renderRoot.querySelectorAll(".settings-category")].map((e=>this.observer?.observe(e)))}renderCategoryTree(e,t){return html`
      <ul data-level=${t}>
        ${e.map((e=>html`
          <li ?hidden=${e.__hidden}>
            <a href=${`#category-headline-${e.key}`}
              @click=${()=>this.activeCategory=e.key}
              class="settings-navigation-item ${this.activeCategory===e.key?"active":""}">
              <span class="settings-navigation-item-icon">
                <typo3-backend-icon identifier=${e.icon?e.icon:"actions-dot"} size="small"></typo3-backend-icon>
              </span>
              <span class="settings-navigation-item-label">${e.label}</span>
            </a>
            ${0===e.categories.length?nothing:html`
              ${this.renderCategoryTree(e.categories,t+1)}
            `}
          </li>
        `))}
      </ul>
    `}renderSettings(e,t){return e.map((e=>html`
      <div class="settings-category-list" data-key=${e.key}>
        <div class="settings-category" data-key=${e.key} ?hidden=${e.__hidden}>
          ${this.renderHeadline(Math.min(t+1,6),`category-headline-${e.key}`,html`${e.label}`)}
          ${e.description?html`<p>${e.description}</p>`:nothing}
        </div>
        ${e.settings.map((e=>html`
          <typo3-backend-editable-setting
              ?hidden=${e.__hidden}
              .setting=${e}
              .dumpuri=${this.dumpUrl}
              ?debug=${this.debug}
          ></typo3-backend-editable-setting>
        `))}
      </div>
      ${0===e.categories.length?nothing:html`
        ${this.renderSettings(e.categories,t+1)}
      `}
    `))}renderHeadline(e,t,i){switch(e){case 1:return html`<h1 id=${t}>${i}</h1>`;case 2:return html`<h2 id=${t}>${i}</h2>`;case 3:return html`<h3 id=${t}>${i}</h3>`;case 4:return html`<h4 id=${t}>${i}</h4>`;case 5:return html`<h5 id=${t}>${i}</h5>`;case 6:return html`<h6 id=${t}>${i}</h6>`;default:throw new Error(`Invalid header level: ${e}`)}}async onSubmit(e){const t=e.target;if("export"===e.submitter?.value){e.preventDefault();const i=new FormData(t),r=await new AjaxRequest(this.dumpUrl).post(i),s=await r.resolve();"string"==typeof s.yaml?copyToClipboard(s.yaml):(console.warn("Value can not be copied to clipboard.",typeof s.yaml),Notification.error(lll("copyToClipboard.error")))}}async onSearch(e){e.preventDefault(),this.searchTerm=e.currentTarget.value}render(){const e=this.filterCategories(),t=e.filter((e=>!e.__hidden)).length>0;return html`
      <form class="settings-container"
            id="sitesettings_form"
            name="sitesettings_form"
            action=${this.actionUrl}
            method="post"
            @submit=${e=>this.onSubmit(e)}
      >
        ${Object.entries(this.customFormData).map((([e,t])=>html`
          <input type="hidden" name=${e} value=${t}>
        `))}

        <div class="settings-search form-group">
          <label for="settings-search" class="visually-hidden">
            ${lll("edit.searchTermVisuallyHiddenLabel")}
          </label>
          <input
            type="search"
            id="settings-search"
            class="form-control"
            placeholder=${lll("edit.searchTermPlaceholder")}
            .value=${live(this.searchTerm)}
            @change=${e=>this.onSearch(e)}
            @input=${e=>this.onSearch(e)}>
        </div>

        ${t?nothing:html`
          <div class="callout callout-info">
            <div class="callout-icon">
              <span class="icon-emphasized">
                <typo3-backend-icon identifier="actions-info" size="small"></typo3-backend-icon>
              </span>
            </div>
            <div class="callout-content">
              <div class="callout-title">${lll("edit.search.noResultsTitle")}</div>
              <div class="callout-body">
                <p>${lll("edit.search.noResultsMessage")}</p>
                <button
                    type="button"
                    class="btn btn-default"
                    @click=${()=>this.searchTerm=""}
                  >${lll("edit.search.noResultsResetButtonLabel")}</button>
              </div>
            </div>
          </div>
        `}

        <div class="settings" ?hidden=${!t}>
          <div class="settings-navigation">
            ${this.renderCategoryTree(e??[],1)}
          </div>
          <div class="settings-body">
            ${this.renderSettings(e??[],1)}
          </div>
        </div>
      </form>
    `}filterCategories(e=null){return e??(e=this.categories),e.map((e=>{const t=this.filterSettings(e.settings),i=this.filterCategories(e.categories),r=t.filter((e=>!e.__hidden)).length>0,s=i.filter((e=>!e.__hidden)).length>0;return{...e,settings:t,categories:i,__hidden:!r&&!s}}))}filterSettings(e){return e.map((e=>({...e,__hidden:!(this.matchesSearchTerm(e.definition.key)||this.matchesSearchTerm(e.definition.label)||this.matchesSearchTerm(e.definition.description??"")||this.valueMatchesSearchTerm(e.value)||e.definition.tags.filter((e=>this.matchesSearchTerm(e))).length>0)})))}matchesSearchTerm(e){return""===this.searchTerm||this.matchesSubstring(e,this.searchTerm)}valueMatchesSearchTerm(e){return"string"==typeof e?this.matchesSearchTerm(e):!!Array.isArray(e)&&e.filter((e=>"string"==typeof e&&this.matchesSearchTerm(e))).length>0}matchesSubstring(e,t){return e.toLowerCase().includes(t.toLowerCase())}};__decorate([property({type:Array})],SettingsEditorElement.prototype,"categories",void 0),__decorate([property({type:String,attribute:"action-url"})],SettingsEditorElement.prototype,"actionUrl",void 0),__decorate([property({type:String,attribute:"dump-url"})],SettingsEditorElement.prototype,"dumpUrl",void 0),__decorate([property({type:Object,attribute:"custom-form-data"})],SettingsEditorElement.prototype,"customFormData",void 0),__decorate([property({type:Boolean})],SettingsEditorElement.prototype,"debug",void 0),__decorate([state()],SettingsEditorElement.prototype,"searchTerm",void 0),__decorate([state()],SettingsEditorElement.prototype,"activeCategory",void 0),SettingsEditorElement=__decorate([customElement("typo3-backend-settings-editor")],SettingsEditorElement);export{SettingsEditorElement};