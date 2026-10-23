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
import{html as l,nothing as a}from"lit";import{property as f,customElement as d}from"lit/decorators.js";import{live as u}from"lit/directives/live.js";import{BaseElement as $}from"@typo3/backend/settings/type/base.js";var h=function(r,e,t,i){var o=arguments.length,n=o<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,t):i,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(r,e,t,i);else for(var m=r.length-1;m>=0;m--)(s=r[m])&&(n=(o<3?s(n):o>3?s(e,t,n):s(e,t))||n);return o>3&&n&&Object.defineProperty(e,t,n),n};const c="typo3-backend-settings-type-int";let p=class extends ${constructor(){super(...arguments),this.value=0}handleChange(e){const t=e.target;t.reportValidity()&&(t instanceof HTMLInputElement?this.value=t.valueAsNumber:this.value=parseInt(t.value,10))}renderEnum(){return l`<select id=${this.formid} class=form-select ?readonly=${this.readonly} .value=${u(this.value)} @change=${this.handleChange}>${Object.entries(this.enum??{}).map(([e,t])=>l`<option ?selected=${this.value.toString()===e} value=${e}>${t}${this.debug?l`[${e}]`:a}</option>`)}</select>`}render(){return typeof this.enum=="object"?this.renderEnum():l`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${u(String(this.value))} required min=${this.options.min??a} max=${this.options.max??a} step=${this.options.step??a} @change=${this.handleChange}>`}};h([f({type:Number})],p.prototype,"value",void 0),p=h([d(c)],p);export{p as IntTypeElement,c as componentName};
