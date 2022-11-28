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
var __decorate=function(e,t,i,r){var o,c=arguments.length,n=c<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,i,r);else for(var l=e.length-1;l>=0;l--)(o=e[l])&&(n=(c<3?o(n):c>3?o(t,i,n):o(t,i))||n);return c>3&&n&&Object.defineProperty(t,i,n),n};import{customElement,property}from"lit/decorators.js";import{ifDefined}from"lit/directives/if-defined.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let Action=class extends LitElement{connectedCallback(){super.connectedCallback(),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}createRenderRoot(){return this}render(){return html`
      <div>
        <div class="livesearch-result-item-icon">
          <typo3-backend-icon identifier="${ifDefined(this.resultItemAction.icon.identifier||"actions-arrow-right")}" overlay="${this.resultItemAction.icon.overlay}" size="small"></typo3-backend-icon>
        </div>
        <div class="livesearch-result-item-title">
          ${this.resultItemAction.label}
        </div>
      </div>
    `}};__decorate([property({type:Object,attribute:!1})],Action.prototype,"resultItem",void 0),__decorate([property({type:Object,attribute:!1})],Action.prototype,"resultItemAction",void 0),Action=__decorate([customElement("typo3-backend-live-search-result-item-action")],Action);export{Action};