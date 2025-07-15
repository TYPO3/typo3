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
import{html as u,nothing as c}from"lit";import{property as f,customElement as h}from"lit/decorators.js";import{live as d}from"lit/directives/live.js";import{BaseElement as v}from"@typo3/backend/settings/type/base.js";var m=function(n,e,t,o){var l=arguments.length,r=l<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,t):o,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")r=Reflect.decorate(n,e,t,o);else for(var a=n.length-1;a>=0;a--)(i=n[a])&&(r=(l<3?i(r):l>3?i(e,t,r):i(e,t))||r);return l>3&&r&&Object.defineProperty(e,t,r),r};const s="typo3-backend-settings-type-url";let p=class extends v{constructor(){super(...arguments),this.value=""}handleChange(e){const t=e.target;t.reportValidity()&&(this.value=t.value.trim())}render(){return u`<input type=url id=${this.formid} class=form-control .value=${d(this.value)} ?readonly=${this.readonly} pattern=${this.options.pattern??c} @change=${this.handleChange}>`}};m([f({type:String})],p.prototype,"value",void 0),p=m([h(s)],p);export{p as UrlTypeElement,s as componentName};
