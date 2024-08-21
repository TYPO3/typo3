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
var __decorate=function(e,t,r,n){var a,i=arguments.length,s=i<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,n);else for(var o=e.length-1;o>=0;o--)(a=e[o])&&(s=(i<3?a(s):i>3?a(t,r,s):a(t,r))||s);return i>3&&s&&Object.defineProperty(t,r,s),s};import{customElement,property,state}from"lit/decorators.js";import{html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";let CharCounter=class extends LitElement{constructor(){super(...arguments),this.remainingCharacters=0,this.targetElement=null,this.threshold=15,this.onInput=e=>{this.determineRemainingCharacters(e.target)},this.onFocus=e=>{this.determineRemainingCharacters(e.target),this.hidden=!1},this.onBlur=()=>{this.hidden=!0}}connectedCallback(){super.connectedCallback(),this.registerCallbacks(),this.hidden=!0}disconnectedCallback(){super.disconnectedCallback(),this.removeCallbacks()}createRenderRoot(){return this}updated(e){e.has("target")&&(this.removeCallbacks(),this.targetElement=document.querySelector(this.target),this.registerCallbacks())}render(){return html`
      <span class="form-hint form-hint--${this.determineCounterClass()}">
        ${lll("FormEngine.remainingCharacters").replace("{0}",this.remainingCharacters.toString(10))}
      </span>
    `}registerCallbacks(){null!==this.targetElement&&(this.targetElement.addEventListener("input",this.onInput),this.targetElement.addEventListener("focus",this.onFocus),this.targetElement.addEventListener("blur",this.onBlur))}removeCallbacks(){null!==this.targetElement&&(this.targetElement.removeEventListener("input",this.onInput),this.targetElement.removeEventListener("focus",this.onFocus),this.targetElement.removeEventListener("blur",this.onBlur))}determineRemainingCharacters(e){const t=e.value,r=t.length,n=(t.match(/\n/g)||[]).length;this.remainingCharacters=this.targetElement.maxLength-r-n}determineCounterClass(){return this.remainingCharacters<this.threshold?"danger":this.remainingCharacters<2*this.threshold?"warning":"info"}};__decorate([property()],CharCounter.prototype,"target",void 0),__decorate([state()],CharCounter.prototype,"remainingCharacters",void 0),CharCounter=__decorate([customElement("typo3-backend-formengine-char-counter")],CharCounter);export{CharCounter};