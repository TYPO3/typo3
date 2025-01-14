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
var __decorate=function(e,t,o,i){var n,a=arguments.length,c=a<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,o):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(e,t,o,i);else for(var s=e.length-1;s>=0;s--)(n=e[s])&&(c=(a<3?n(c):a>3?n(t,o,c):n(t,o))||c);return a>3&&c&&Object.defineProperty(t,o,c),c};import{html,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import"@typo3/backend/element/icon-element.js";let ColorSchemeSwitchElement=class extends LitElement{constructor(){super(...arguments),this.activeColorScheme=null,this.colorSchemes=null,this.advancedOptionsExpanded=!1,this.autoDetect=null,this.mql=null,this.mediaQueryListener=e=>this.autoDetect=e.matches?"dark":"light"}connectedCallback(){super.connectedCallback(),this.mql=window.matchMedia("(prefers-color-scheme: dark)"),this.mediaQueryListener(this.mql),this.mql.addEventListener("change",this.mediaQueryListener)}disconnectedCallback(){super.disconnectedCallback(),this.mql.removeEventListener("change",this.mediaQueryListener),this.mql=null}createRenderRoot(){return this}getRealColorScheme(){return"auto"===this.activeColorScheme?this.autoDetect??"light":this.activeColorScheme??"light"}render(){return html`
      <div class="btn-group">
        <button
            type="button"
            class="btn btn-default"
            title=${this.label}
            @click=${e=>this.toggle(e)}
        >
          <typo3-backend-icon identifier=${this.getIcon(this.activeColorScheme??"auto")} size="small"></typo3-backend-icon>
          ${this.getLabel(this.getRealColorScheme())}
          <typo3-backend-icon identifier="actions-exchange" size="small" style="margin-left: auto;"></typo3-backend-icon>
        </button>

        <button
            type="button"
            class="btn btn-default ${this.advancedOptionsExpanded?"active":""}"
            aria-haspopup="true"
            aria-expanded=${this.advancedOptionsExpanded?"true":"false"}
            @click=${e=>{e.stopPropagation(),this.advancedOptionsExpanded=!this.advancedOptionsExpanded}}
            >
          <span class="visually-hidden">Show more options</span>
          <typo3-backend-icon identifier=${this.advancedOptionsExpanded?"actions-chevron-up":"actions-chevron-down"} size="small"></typo3-backend-icon>
        </button>
      </div>
      ${!1===this.advancedOptionsExpanded?nothing:html`
        <ul class="dropdown-list">
          ${this.colorSchemes.map((e=>this.renderItem(e)))}
        </ul>
      `}
    `}getIcon(e){return this.colorSchemes.find((t=>t.value===e))?.icon??"auto"}getLabel(e){return this.colorSchemes.find((t=>t.value===e))?.label??""}renderItem(e){return html`
      <li>
        <button class="dropdown-item" @click="${t=>this.handleClick(t,e.value)}" aria-current="${this.activeColorScheme===e.value?"true":"false"}">
          <span class="dropdown-item-columns">
            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
              <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-title">
              ${e.label}
              ${"auto"===e.value?html`<span class="dropdown-item-column-title-info">${this.getLabel(this.autoDetect)}</span>`:""}
            </span>
            ${this.activeColorScheme===e.value?html`
              <span class="text-primary">
                <typo3-backend-icon identifier="actions-dot" size="small"></typo3-backend-icon>
              </span>
            `:html`
              <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
            `}
          </span>
        </button>
      </li>
    `}async toggle(e){e.preventDefault(),e.stopPropagation();let t="dark"===this.getRealColorScheme()?"light":"dark";t===this.autoDetect&&(t="auto"),this.triggerSchemeUpdate(t),await this.persistSchemeUpdate(t)}async handleClick(e,t){e.preventDefault(),e.stopPropagation(),this.triggerSchemeUpdate(t),await this.persistSchemeUpdate(t),this.advancedOptionsExpanded=!1}async persistSchemeUpdate(e){const t=new URL(TYPO3.settings.ajaxUrls.color_scheme_update,window.location.origin);return await new AjaxRequest(t).post({colorScheme:e})}triggerSchemeUpdate(e){document.dispatchEvent(new CustomEvent("typo3:color-scheme:update",{detail:{colorScheme:e}}))}};__decorate([property({type:String})],ColorSchemeSwitchElement.prototype,"activeColorScheme",void 0),__decorate([property({type:Array})],ColorSchemeSwitchElement.prototype,"colorSchemes",void 0),__decorate([property({type:String})],ColorSchemeSwitchElement.prototype,"label",void 0),__decorate([state()],ColorSchemeSwitchElement.prototype,"advancedOptionsExpanded",void 0),__decorate([state()],ColorSchemeSwitchElement.prototype,"autoDetect",void 0),ColorSchemeSwitchElement=__decorate([customElement("typo3-backend-color-scheme-switch")],ColorSchemeSwitchElement);export{ColorSchemeSwitchElement};