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
var __decorate=function(e,t,o,r){var c,n=arguments.length,i=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,o,r);else for(var l=e.length-1;l>=0;l--)(c=e[l])&&(i=(n<3?c(i):n>3?c(t,o,i):c(t,o))||i);return n>3&&i&&Object.defineProperty(t,o,i),i};import{html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import"@typo3/backend/element/icon-element.js";let ColorSchemeSwitchElement=class extends LitElement{constructor(){super(...arguments),this.activeColorScheme=null,this.data=null}createRenderRoot(){return this}render(){return html`
      <ul class="dropdown-list">
        ${this.data.map((e=>this.renderItem(e)))}
      </ul>
    `}renderItem(e){return html`
      <li>
        <button class="dropdown-item" @click="${()=>this.handleClick(e.value)}" aria-current="${this.activeColorScheme===e.value?"true":"false"}">
          <span class="dropdown-item-columns">
            ${this.activeColorScheme===e.value?html`
              <span class="text-primary">
                <typo3-backend-icon identifier="actions-dot" size="small"></typo3-backend-icon>
              </span>
            `:html`
              <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
            `}
            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
              <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-title">
              ${e.label}
            </span>
            <slot></slot>
          </span>
        </button>
      </li>
    `}async handleClick(e){this.triggerSchemeUpdate(e),await this.persistSchemeUpdate(e)}async persistSchemeUpdate(e){const t=new URL(TYPO3.settings.ajaxUrls.color_scheme_update,window.location.origin);return await new AjaxRequest(t).post({colorScheme:e})}triggerSchemeUpdate(e){document.dispatchEvent(new CustomEvent("typo3:color-scheme:update",{detail:{colorScheme:e}}))}};__decorate([property({type:String})],ColorSchemeSwitchElement.prototype,"activeColorScheme",void 0),__decorate([property({type:Array})],ColorSchemeSwitchElement.prototype,"data",void 0),ColorSchemeSwitchElement=__decorate([customElement("typo3-backend-color-scheme-switch")],ColorSchemeSwitchElement);export{ColorSchemeSwitchElement};