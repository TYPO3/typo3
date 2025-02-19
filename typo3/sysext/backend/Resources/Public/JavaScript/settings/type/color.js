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
import{html as m}from"lit";import{property as f,customElement as s}from"lit/decorators.js";import{BaseElement as d}from"@typo3/backend/settings/type/base.js";import"@typo3/backend/color-picker.js";import y from"@typo3/core/event/regular-event.js";var a=function(r,e,o,n){var l=arguments.length,t=l<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,o,n);else for(var u=r.length-1;u>=0;u--)(p=r[u])&&(t=(l<3?p(t):l>3?p(e,o,t):p(e,o))||t);return l>3&&t&&Object.defineProperty(e,o,t),t};const c="typo3-backend-settings-type-color";let i=class extends d{firstUpdated(){const e=this.getInputElement();e&&new y("blur",o=>{this.updateValue(o.target.value)}).bindTo(e)}updateValue(e){this.value=e}render(){return m`<typo3-backend-color-picker><input type=text id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.updateValue(e.target.value)}></typo3-backend-color-picker>`}getInputElement(){return this.querySelector("input")}};a([f({type:String})],i.prototype,"value",void 0),i=a([s(c)],i);export{i as ColorTypeElement,c as componentName};
