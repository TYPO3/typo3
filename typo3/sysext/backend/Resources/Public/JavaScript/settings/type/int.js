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
import{html as u,nothing as s}from"lit";import{property as c,customElement as h}from"lit/decorators.js";import{live as d}from"lit/directives/live.js";import{BaseElement as y}from"@typo3/backend/settings/type/base.js";var a=function(o,e,t,r){var i=arguments.length,n=i<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,t):r,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(o,e,t,r);else for(var m=o.length-1;m>=0;m--)(p=o[m])&&(n=(i<3?p(n):i>3?p(e,t,n):p(e,t))||n);return i>3&&n&&Object.defineProperty(e,t,n),n};const f="typo3-backend-settings-type-int";let l=class extends y{handleChange(e){const t=e.target;t.reportValidity()&&(this.value=t.valueAsNumber)}render(){return u`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${d(String(this.value))} required min=${this.options.min??s} max=${this.options.max??s} step=${this.options.step??s} @change=${this.handleChange}>`}};a([c({type:Number})],l.prototype,"value",void 0),l=a([h(f)],l);export{l as IntTypeElement,f as componentName};
