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
import{property as s,customElement as h}from"lit/decorators.js";import{LitElement as d,html as v}from"lit";import m from"@typo3/backend/storage/browser-session.js";var n=function(r,o,i,p){var c=arguments.length,t=c<3?o:p===null?p=Object.getOwnPropertyDescriptor(o,i):p,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,o,i,p);else for(var l=r.length-1;l>=0;l--)(a=r[l])&&(t=(c<3?a(t):c>3?a(o,i,t):a(o,i))||t);return c>3&&t&&Object.defineProperty(o,i,t),t};let e=class extends d{constructor(){super(...arguments),this.active=!1}connectedCallback(){this.parentContainer=this.closest("typo3-backend-live-search"),super.connectedCallback()}createRenderRoot(){return this}render(){return v`<div class=form-check><input type=checkbox class=form-check-input name=${this.optionName}[] value=${this.optionId} id=${this.optionId} ?checked=${this.active} @input=${this.handleInput}> <label class=form-check-label for=${this.optionId}>${this.optionLabel}</label></div>`}getStorageKey(){return`livesearch-option-${this.optionName}-${this.optionId}`}handleInput(){this.active=!this.active,this.parentContainer.dispatchEvent(new CustomEvent("typo3:live-search:option-invoked",{detail:{active:this.active}})),m.set(this.getStorageKey(),this.active?"1":"0")}};n([s({type:Boolean})],e.prototype,"active",void 0),n([s({type:String})],e.prototype,"optionId",void 0),n([s({type:String})],e.prototype,"optionName",void 0),n([s({type:String})],e.prototype,"optionLabel",void 0),e=n([h("typo3-backend-live-search-option-item")],e);export{e as SearchOptionItem};
