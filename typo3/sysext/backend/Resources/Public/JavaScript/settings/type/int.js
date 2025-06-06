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
import{html as l,nothing as p}from"lit";import{property as f,customElement as d}from"lit/decorators.js";import{live as h}from"lit/directives/live.js";import{BaseElement as $}from"@typo3/backend/settings/type/base.js";var u=function(i,e,t,o){var r=arguments.length,n=r<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,t):o,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(i,e,t,o);else for(var m=i.length-1;m>=0;m--)(s=i[m])&&(n=(r<3?s(n):r>3?s(e,t,n):s(e,t))||n);return r>3&&n&&Object.defineProperty(e,t,n),n};const c="typo3-backend-settings-type-int";let a=class extends ${handleChange(e){const t=e.target;t.reportValidity()&&(t instanceof HTMLInputElement?this.value=t.valueAsNumber:this.value=parseInt(t.value,10))}renderEnum(){return l`<select id=${this.formid} class=form-select ?readonly=${this.readonly} .value=${h(this.value)} @change=${this.handleChange}>${Object.entries(this.enum).map(([e,t])=>l`<option ?selected=${this.value.toString()===e} value=${e}>${t}${this.debug?l`[${e}]`:p}</option>`)}</select>`}render(){return typeof this.enum=="object"?this.renderEnum():l`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${h(String(this.value))} required min=${this.options.min??p} max=${this.options.max??p} step=${this.options.step??p} @change=${this.handleChange}>`}};u([f({type:Number})],a.prototype,"value",void 0),a=u([d(c)],a);export{a as IntTypeElement,c as componentName};
