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
import{html as s,nothing as m}from"lit";import{property as f,customElement as d}from"lit/decorators.js";import{live as p}from"lit/directives/live.js";import{BaseElement as $}from"@typo3/backend/settings/type/base.js";var c=function(i,e,t,o){var r=arguments.length,n=r<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,t):o,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(i,e,t,o);else for(var h=i.length-1;h>=0;h--)(l=i[h])&&(n=(r<3?l(n):r>3?l(e,t,n):l(e,t))||n);return r>3&&n&&Object.defineProperty(e,t,n),n};const u="typo3-backend-settings-type-string";let a=class extends ${handleChange(e){const t=e.target;t.reportValidity()&&(this.value=t.value)}renderEnum(){return s`<select id=${this.formid} class=form-select ?readonly=${this.readonly} .value=${p(this.value)} @change=${this.handleChange}>${Object.entries(this.enum).map(([e,t])=>s`<option ?selected=${this.value===e} value=${e}>${t}${this.debug?s`[${e}]`:m}</option>`)}</select>`}render(){return typeof this.enum=="object"?this.renderEnum():s`<input type=text id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${p(this.value)} minlength=${this.options.min??m} maxlength=${this.options.max??m} @change=${this.handleChange}>`}};c([f({type:String})],a.prototype,"value",void 0),a=c([d(u)],a);export{a as StringTypeElement,u as componentName};
