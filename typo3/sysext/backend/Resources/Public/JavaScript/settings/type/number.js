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
import{html as c,nothing as s}from"lit";import{property as f,customElement as h}from"lit/decorators.js";import{live as d}from"lit/directives/live.js";import{BaseElement as v}from"@typo3/backend/settings/type/base.js";var u=function(r,e,t,o){var i=arguments.length,n=i<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,t):o,m;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(r,e,t,o);else for(var l=r.length-1;l>=0;l--)(m=r[l])&&(n=(i<3?m(n):i>3?m(e,t,n):m(e,t))||n);return i>3&&n&&Object.defineProperty(e,t,n),n};const a="typo3-backend-settings-type-number";let p=class extends v{constructor(){super(...arguments),this.value=0}handleChange(e){const t=e.target;t.reportValidity()&&(this.value=t.valueAsNumber)}render(){return c`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${d(this.value)} required min=${this.options.min??s} max=${this.options.max??s} step=${this.options.step??"0.01"} @change=${this.handleChange}>`}};u([f({type:Number})],p.prototype,"value",void 0),p=u([h(a)],p);export{p as NumberTypeElement,a as componentName};
