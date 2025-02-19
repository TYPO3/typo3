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
import{html as s,nothing as u}from"lit";import{property as f,customElement as h}from"lit/decorators.js";import{BaseElement as d}from"@typo3/backend/settings/type/base.js";var c=function(r,e,n,o){var i=arguments.length,t=i<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,n):o,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,n,o);else for(var a=r.length-1;a>=0;a--)(l=r[a])&&(t=(i<3?l(t):i>3?l(e,n,t):l(e,n))||t);return i>3&&t&&Object.defineProperty(e,n,t),t};const m="typo3-backend-settings-type-string";let p=class extends d{renderEnum(){return s`<select id=${this.formid} class=form-select ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.value=e.target.value}>${Object.entries(this.enum).map(([e,n])=>s`<option ?selected=${this.value===e} value=${e}>${n}${this.debug?s`[${e}]`:u}</option>`)}</select>`}render(){return typeof this.enum=="object"?this.renderEnum():s`<input type=text id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.value=e.target.value}>`}};c([f({type:String})],p.prototype,"value",void 0),p=c([h(m)],p);export{p as StringTypeElement,m as componentName};
