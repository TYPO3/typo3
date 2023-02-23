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
var __decorate=function(e,t,n,o){var i,r=arguments.length,s=r<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,n):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,n,o);else for(var l=e.length-1;l>=0;l--)(i=e[l])&&(s=(r<3?i(s):r>3?i(t,n,s):i(t,n))||s);return r>3&&s&&Object.defineProperty(t,n,s),s};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let ResultItem=class extends LitElement{connectedCallback(){super.connectedCallback(),this.addEventListener("blur",this.onBlur),this.addEventListener("click",this.onClick),this.addEventListener("keyup",this.onKeyUp)}disconnectedCallback(){this.removeEventListener("blur",this.onBlur),this.removeEventListener("click",this.onClick),this.removeEventListener("keyup",this.onKeyUp),super.disconnectedCallback()}createRenderRoot(){return this}render(){return html`
      <div class="formengine-suggest-result-item-icon">
        <typo3-backend-icon title="${this.icon.title}" identifier="${this.icon.identifier}" overlay="${this.icon.overlay}" size="small"></typo3-backend-icon>
      </div>
      <div class="formengine-suggest-result-item-label">
        ${this.label} <small>[${this.uid}] ${this.path}</small>
      </div>
    `}onBlur(e){let t=!0;const n=e.relatedTarget,o=this.closest("typo3-backend-formengine-suggest-result-container");"typo3-backend-formengine-suggest-result-item"===n?.tagName.toLowerCase()&&(t=!1),n?.matches('input[type="search"]')&&o.contains(n)&&(t=!1),o.hidden=t}onClick(e){e.preventDefault(),this.dispatchItemChosenEvent(e.currentTarget)}onKeyUp(e){e.preventDefault(),["Enter"," "].includes(e.key)&&this.dispatchItemChosenEvent(document.activeElement)}dispatchItemChosenEvent(e){e.closest("typo3-backend-formengine-suggest-result-container").dispatchEvent(new CustomEvent("typo3:formengine:suggest-item-chosen",{detail:{element:e}}))}};__decorate([property({type:Object})],ResultItem.prototype,"icon",void 0),__decorate([property({type:Number})],ResultItem.prototype,"uid",void 0),__decorate([property({type:String})],ResultItem.prototype,"table",void 0),__decorate([property({type:String})],ResultItem.prototype,"label",void 0),__decorate([property({type:String})],ResultItem.prototype,"path",void 0),ResultItem=__decorate([customElement("typo3-backend-formengine-suggest-result-item")],ResultItem);export{ResultItem};