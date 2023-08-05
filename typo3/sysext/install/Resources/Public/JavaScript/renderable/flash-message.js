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
var __decorate=function(e,t,s,r){var o,a=arguments.length,l=a<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,s):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,s,r);else for(var n=e.length-1;n>=0;n--)(o=e[n])&&(l=(a<3?o(l):a>3?o(t,s,l):o(t,s))||l);return a>3&&l&&Object.defineProperty(t,s,l),l};import Severity from"@typo3/install/renderable/severity.js";import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";let FlashMessage=class extends LitElement{static create(e,t,s=""){const r=document.createElement("typo3-install-flashmessage");return r.severity=e,r.subject=t,s&&(r.content=s),r}createRenderRoot(){return this}render(){let e=nothing;return this.content&&(e=html`<p class="messageText">${this.content}</p>`),html`
      <div class="t3js-message typo3-message alert alert-${Severity.getCssClass(this.severity)}">
        <h4>${this.subject}</h4>
        ${e}
      </div>
    `}};__decorate([property({type:Number})],FlashMessage.prototype,"severity",void 0),__decorate([property({type:String})],FlashMessage.prototype,"subject",void 0),__decorate([property({type:String})],FlashMessage.prototype,"content",void 0),FlashMessage=__decorate([customElement("typo3-install-flashmessage")],FlashMessage);export{FlashMessage};