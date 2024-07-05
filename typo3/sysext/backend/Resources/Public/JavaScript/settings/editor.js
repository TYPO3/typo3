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
var __decorate=function(t,e,r,i){var o,n=arguments.length,s=n<3?e:null===i?i=Object.getOwnPropertyDescriptor(e,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,r,i);else for(var a=t.length-1;a>=0;a--)(o=t[a])&&(s=(n<3?o(s):n>3?o(e,r,s):o(e,r))||s);return n>3&&s&&Object.defineProperty(e,r,s),s};import{html,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{copyToClipboard}from"@typo3/backend/copy-to-clipboard.js";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/settings/editor/editable-setting.js";import"@typo3/backend/settings/type/bool.js";import"@typo3/backend/settings/type/int.js";import"@typo3/backend/settings/type/number.js";import"@typo3/backend/settings/type/string.js";import"@typo3/backend/settings/type/stringlist.js";let SettingsEditorElement=class extends LitElement{constructor(){super(...arguments),this.activeCategory="",this.visibleCategories={},this.observer=null}createRenderRoot(){return this}firstUpdated(){this.observer=new IntersectionObserver((t=>{t.forEach((t=>{const e=t.target.dataset.key;this.visibleCategories[e]=t.isIntersecting}));const e=t=>t.reduce(((t,r)=>[...t,r.key,...e(r.categories)]),[]),r=e(this.categories).filter((t=>this.visibleCategories[t]))[0]||"";r&&(this.activeCategory=r)}),{root:document.querySelector(".module"),threshold:.1,rootMargin:`-${getComputedStyle(document.querySelector(".module-docheader")).getPropertyValue("min-height")} 0px 0px 0px`})}updated(){[...this.renderRoot.querySelectorAll(".settings-category")].map((t=>this.observer?.observe(t)))}renderCategoryTree(t,e){return html`
      <ul data-level=${e}>
        ${t.map((t=>html`
          <li>
            <a href=${`#category-headline-${t.key}`}
              @click=${()=>this.activeCategory=t.key}
              class="settings-navigation-item ${this.activeCategory===t.key?"active":""}">
              <span class="settings-navigation-item-icon">
                <typo3-backend-icon identifier=${t.icon?t.icon:"actions-dot"} size="small"></typo3-backend-icon>
              </span>
              <span class="settings-navigation-item-label">${t.label}</span>
            </a>
            ${0===t.categories.length?nothing:html`
              ${this.renderCategoryTree(t.categories,e+1)}
            `}
          </li>
        `))}
      </ul>
    `}renderSettings(t,e){return t.map((t=>html`
      <div class="settings-category-list" data-key=${t.key}>
        <div class="settings-category" data-key=${t.key}>
          ${this.renderHeadline(Math.min(e+1,6),`category-headline-${t.key}`,html`${t.label}`)}
          ${t.description?html`<p>${t.description}</p>`:nothing}
        </div>
        ${t.settings.map((t=>html`
          <typo3-backend-editable-setting .setting=${t} .dumpuri=${this.dumpUrl}></typo3-backend-editable-setting>
        `))}
      </div>
      ${0===t.categories.length?nothing:html`
        ${this.renderSettings(t.categories,e+1)}
      `}
    `))}renderHeadline(t,e,r){switch(t){case 1:return html`<h1 id=${e}>${r}</h1>`;case 2:return html`<h2 id=${e}>${r}</h2>`;case 3:return html`<h3 id=${e}>${r}</h3>`;case 4:return html`<h4 id=${e}>${r}</h4>`;case 5:return html`<h5 id=${e}>${r}</h5>`;case 6:return html`<h6 id=${e}>${r}</h6>`;default:throw new Error(`Invalid header level: ${t}`)}}async onSubmit(t){const e=t.target;if("export"===t.submitter?.value){t.preventDefault();const r=new FormData(e),i=await new AjaxRequest(this.dumpUrl).post(r),o=await i.resolve();"string"==typeof o.yaml?copyToClipboard(o.yaml):(console.warn("Value can not be copied to clipboard.",typeof o.yaml),Notification.error(lll("copyToClipboard.error")))}}render(){return html`
      <form class="settings-container"
            id="sitesettings_form"
            name="sitesettings_form"
            action=${this.actionUrl}
            method="post"
            @submit=${t=>this.onSubmit(t)}
      >
        ${this.returnUrl?html`<input type="hidden" name="returnUrl" value=${this.returnUrl} />`:nothing}
        <div class="settings">
          <div class="settings-navigation">
            ${this.renderCategoryTree(this.categories??[],1)}
          </div>
          <div class="settings-body">
            ${this.renderSettings(this.categories??[],1)}
          </div>
        </div>
      </form>
    `}};__decorate([property({type:Array})],SettingsEditorElement.prototype,"categories",void 0),__decorate([property({type:String,attribute:"action-url"})],SettingsEditorElement.prototype,"actionUrl",void 0),__decorate([property({type:String,attribute:"dump-url"})],SettingsEditorElement.prototype,"dumpUrl",void 0),__decorate([property({type:String,attribute:"return-url"})],SettingsEditorElement.prototype,"returnUrl",void 0),__decorate([state()],SettingsEditorElement.prototype,"activeCategory",void 0),SettingsEditorElement=__decorate([customElement("typo3-backend-settings-editor")],SettingsEditorElement);export{SettingsEditorElement};