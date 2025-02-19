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
import{html as u}from"lit";import{property as s,customElement as a}from"lit/decorators.js";import{BaseElement as h}from"@typo3/backend/settings/type/base.js";var m=function(e,t,r,n){var c=arguments.length,o=c<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,r):n,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(e,t,r,n);else for(var i=e.length-1;i>=0;i--)(l=e[i])&&(o=(c<3?l(o):c>3?l(t,r,o):l(t,r))||o);return c>3&&o&&Object.defineProperty(t,r,o),o};const f="typo3-backend-settings-type-bool";let p=class extends h{render(){return u`<div class="form-check form-check-type-toggle"><input type=checkbox id=${this.formid} class=form-check-input value=1 ?disabled=${this.readonly} .checked=${this.value} @change=${t=>this.value=t.target.checked}></div>`}};m([s({type:Boolean,converter:{toAttribute:e=>e?"1":"0",fromAttribute:e=>e==="1"||e==="true"}})],p.prototype,"value",void 0),p=m([a(f)],p);export{p as BoolTypeElement,f as componentName};
