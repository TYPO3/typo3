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
import f from"@typo3/install/renderable/severity.js";import{topDocument as u}from"@typo3/core/utility/top-window.js";import{property as p,customElement as v}from"lit/decorators.js";import{LitElement as h,nothing as y,html as m}from"lit";var c=function(i,t,s,r){var n=arguments.length,e=n<3?t:r===null?r=Object.getOwnPropertyDescriptor(t,s):r,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,s,r);else for(var a=i.length-1;a>=0;a--)(l=i[a])&&(e=(n<3?l(e):n>3?l(t,s,e):l(t,s))||e);return n>3&&e&&Object.defineProperty(t,s,e),e};let o=class extends h{static create(t,s,r=""){const e=u.createElement("typo3-install-flashmessage");return e.severity=t,e.subject=s,r&&(e.content=r),e}createRenderRoot(){return this}render(){let t=y;return this.content&&(t=m`<p class=alert-message>${this.content}</p>`),m`<div class="t3js-message alert alert-${f.getCssClass(this.severity)}"><div class=alert-title>${this.subject}</div>${t}</div>`}};c([p({type:Number})],o.prototype,"severity",void 0),c([p({type:String})],o.prototype,"subject",void 0),c([p({type:String})],o.prototype,"content",void 0),o=c([v("typo3-install-flashmessage")],o);export{o as FlashMessage};
