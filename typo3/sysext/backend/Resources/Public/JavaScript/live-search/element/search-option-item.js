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
var __decorate=function(e,t,o,i){var r,n=arguments.length,c=n<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,o):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(e,t,o,i);else for(var p=e.length-1;p>=0;p--)(r=e[p])&&(c=(n<3?r(c):n>3?r(t,o,c):r(t,o))||c);return n>3&&c&&Object.defineProperty(t,o,c),c};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import BrowserSession from"@typo3/backend/storage/browser-session.js";import{ifDefined}from"lit/directives/if-defined.js";let SearchOptionItem=class extends LitElement{constructor(){super(...arguments),this.active=!1}connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search"),super.connectedCallback()}createRenderRoot(){return this}render(){return html`
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="${this.optionName}[]" value="${this.optionId}" id="${this.optionId}" checked=${ifDefined(this.active?"checked":void 0)} @input="${this.handleInput}">
        <label class="form-check-label" for="${this.optionId}">
          ${this.optionLabel}
        </label>
      </div>
    `}getStorageKey(){return`livesearch-option-${this.optionName}-${this.optionId}`}handleInput(){this.active=!this.active,this.parentContainer.dispatchEvent(new CustomEvent("typo3:live-search:option-invoked",{detail:{active:this.active}})),BrowserSession.set(this.getStorageKey(),this.active?"1":"0")}};__decorate([property({type:Boolean})],SearchOptionItem.prototype,"active",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionId",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionName",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionLabel",void 0),SearchOptionItem=__decorate([customElement("typo3-backend-live-search-option-item")],SearchOptionItem);export{SearchOptionItem};