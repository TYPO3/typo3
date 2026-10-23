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
import{html as m}from"lit";import{property as s,customElement as f}from"lit/decorators.js";import{BaseElement as d}from"@typo3/backend/settings/type/base.js";import"@typo3/backend/color-picker.js";import h from"@typo3/core/event/regular-event.js";var a=function(o,e,r,n){var l=arguments.length,t=l<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,r,n);else for(var i=o.length-1;i>=0;i--)(p=o[i])&&(t=(l<3?p(t):l>3?p(e,r,t):p(e,r))||t);return l>3&&t&&Object.defineProperty(e,r,t),t};const c="typo3-backend-settings-type-color";let u=class extends d{constructor(){super(...arguments),this.value=""}firstUpdated(){const e=this.getInputElement();e&&new h("blur",r=>{this.updateValue(r.target.value)}).bindTo(e)}updateValue(e){this.value=e}render(){return m`<typo3-backend-color-picker><input type=text id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.updateValue(e.target.value)}></typo3-backend-color-picker>`}getInputElement(){return this.querySelector("input")}};a([s({type:String})],u.prototype,"value",void 0),u=a([f(c)],u);export{u as ColorTypeElement,c as componentName};
