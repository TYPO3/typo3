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
import{html as f,nothing as s}from"lit";import{property as c,customElement as h}from"lit/decorators.js";import{live as d}from"lit/directives/live.js";import{BaseElement as y}from"@typo3/backend/settings/type/base.js";var a=function(o,e,t,r){var i=arguments.length,n=i<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,t):r,m;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(o,e,t,r);else for(var l=o.length-1;l>=0;l--)(m=o[l])&&(n=(i<3?m(n):i>3?m(e,t,n):m(e,t))||n);return i>3&&n&&Object.defineProperty(e,t,n),n};const u="typo3-backend-settings-type-number";let p=class extends y{handleChange(e){const t=e.target;t.reportValidity()&&(this.value=t.valueAsNumber)}render(){return f`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${d(this.value)} required min=${this.options.min??s} max=${this.options.max??s} step=${this.options.step??"0.01"} @change=${this.handleChange}>`}};a([c({type:Number})],p.prototype,"value",void 0),p=a([h(u)],p);export{p as NumberTypeElement,u as componentName};
