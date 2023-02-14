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
var __decorate=function(e,t,o,r){var i,n=arguments.length,p=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)p=Reflect.decorate(e,t,o,r);else for(var c=e.length-1;c>=0;c--)(i=e[c])&&(p=(n<3?i(p):n>3?i(t,o,p):i(t,o))||p);return n>3&&p&&Object.defineProperty(t,o,p),p};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import BrowserSession from"@typo3/backend/storage/browser-session.js";import{ifDefined}from"lit/directives/if-defined.js";let SearchOptionItem=class extends LitElement{constructor(){super(...arguments),this.active=!1}createRenderRoot(){return this}render(){return html`
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="${this.optionName}[]" value="${this.optionId}" id="${this.optionId}" checked=${ifDefined(this.active?"checked":void 0)} @input="${this.handleInput}">
        <label class="form-check-label" for="${this.optionId}">
          ${this.optionLabel}
        </label>
      </div>
    `}getStorageKey(){return`livesearch-option-${this.optionName}-${this.optionId}`}handleInput(){this.active=!this.active,BrowserSession.set(this.getStorageKey(),this.active?"1":"0")}};__decorate([property({type:Boolean})],SearchOptionItem.prototype,"active",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionId",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionName",void 0),__decorate([property({type:String})],SearchOptionItem.prototype,"optionLabel",void 0),SearchOptionItem=__decorate([customElement("typo3-backend-live-search-option-item")],SearchOptionItem);export{SearchOptionItem};