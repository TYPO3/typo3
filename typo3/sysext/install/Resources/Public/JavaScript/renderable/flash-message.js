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
var __decorate=function(e,t,s,r){var o,a=arguments.length,n=a<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,s):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,s,r);else for(var i=e.length-1;i>=0;i--)(o=e[i])&&(n=(a<3?o(n):a>3?o(t,s,n):o(t,s))||n);return a>3&&n&&Object.defineProperty(t,s,n),n};import Severity from"@typo3/install/renderable/severity.js";import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";let FlashMessage=class extends LitElement{static create(e,t,s=""){const r=(window.location!==window.parent.location?window.parent.document:document).createElement("typo3-install-flashmessage");return r.severity=e,r.subject=t,s&&(r.content=s),r}createRenderRoot(){return this}render(){let e=nothing;return this.content&&(e=html`<p class="alert-message">${this.content}</p>`),html`
      <div class="t3js-message alert alert-${Severity.getCssClass(this.severity)}">
        <div class="alert-title">${this.subject}</div>
        ${e}
      </div>
    `}};__decorate([property({type:Number})],FlashMessage.prototype,"severity",void 0),__decorate([property({type:String})],FlashMessage.prototype,"subject",void 0),__decorate([property({type:String})],FlashMessage.prototype,"content",void 0),FlashMessage=__decorate([customElement("typo3-install-flashmessage")],FlashMessage);export{FlashMessage};