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
import a from"@typo3/install/renderable/severity.js";import{topDocument as v}from"@typo3/core/utility/top-window.js";import{property as u,customElement as m}from"lit/decorators.js";import{LitElement as d,nothing as y,html as f}from"lit";var l=function(c,e,o,n){var r=arguments.length,t=r<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(c,e,o,n);else for(var p=c.length-1;p>=0;p--)(s=c[p])&&(t=(r<3?s(t):r>3?s(e,o,t):s(e,o))||t);return r>3&&t&&Object.defineProperty(e,o,t),t};let i=class extends d{constructor(){super(...arguments),this.severity=0,this.subject="",this.content=""}static create(e,o,n=""){const t=v.createElement("typo3-install-infobox");return t.severity=e,t.subject=o,n&&(t.content=n),t}createRenderRoot(){return this}render(){let e=y;return this.content&&(e=f`<div class=callout-body>${this.content}</div>`),f`<div class="t3js-infobox callout callout-sm callout-${a.getCssClass(this.severity)}"><div class=callout-content><div class=callout-title>${this.subject}</div>${e}</div></div>`}};l([u({type:Number})],i.prototype,"severity",void 0),l([u({type:String})],i.prototype,"subject",void 0),l([u({type:String})],i.prototype,"content",void 0),i=l([m("typo3-install-infobox")],i);export{i as InfoBox};
