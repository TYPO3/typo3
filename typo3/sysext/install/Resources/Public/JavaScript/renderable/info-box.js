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
var __decorate=function(t,e,o,r){var n,i=arguments.length,c=i<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(t,e,o,r);else for(var l=t.length-1;l>=0;l--)(n=t[l])&&(c=(i<3?n(c):i>3?n(e,o,c):n(e,o))||c);return i>3&&c&&Object.defineProperty(e,o,c),c};import Severity from"@typo3/install/renderable/severity.js";import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";let InfoBox=class extends LitElement{static create(t,e,o=""){const r=(window.location!==window.parent.location?window.parent.document:document).createElement("typo3-install-infobox");return r.severity=t,r.subject=e,o&&(r.content=o),r}createRenderRoot(){return this}render(){let t=nothing;return this.content&&(t=html`<div class="callout-body">${this.content}</div>`),html`
      <div class="t3js-infobox callout callout-sm callout-${Severity.getCssClass(this.severity)}">
        <div class="callout-content">
          <div class="callout-title">${this.subject}</div>
          ${t}
        </div>
      </div>
    `}};__decorate([property({type:Number})],InfoBox.prototype,"severity",void 0),__decorate([property({type:String})],InfoBox.prototype,"subject",void 0),__decorate([property({type:String})],InfoBox.prototype,"content",void 0),InfoBox=__decorate([customElement("typo3-install-infobox")],InfoBox);export{InfoBox};