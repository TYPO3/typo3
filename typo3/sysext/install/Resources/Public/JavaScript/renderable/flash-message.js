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
import f from"@typo3/install/renderable/severity.js";import{topDocument as u}from"@typo3/core/utility/top-window.js";import{property as p,customElement as h}from"lit/decorators.js";import{LitElement as v,nothing as y,html as m}from"lit";var l=function(i,e,s,r){var n=arguments.length,t=n<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,s):r,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,s,r);else for(var a=i.length-1;a>=0;a--)(c=i[a])&&(t=(n<3?c(t):n>3?c(e,s,t):c(e,s))||t);return n>3&&t&&Object.defineProperty(e,s,t),t};let o=class extends v{constructor(){super(...arguments),this.severity=0,this.subject="",this.content=""}static create(e,s,r=""){const t=u.createElement("typo3-install-flashmessage");return t.severity=e,t.subject=s,r&&(t.content=r),t}createRenderRoot(){return this}render(){let e=y;return this.content&&(e=m`<p class=alert-message>${this.content}</p>`),m`<div class="t3js-message alert alert-${f.getCssClass(this.severity)}"><div class=alert-title>${this.subject}</div>${e}</div>`}};l([p({type:Number})],o.prototype,"severity",void 0),l([p({type:String})],o.prototype,"subject",void 0),l([p({type:String})],o.prototype,"content",void 0),o=l([h("typo3-install-flashmessage")],o);export{o as FlashMessage};
