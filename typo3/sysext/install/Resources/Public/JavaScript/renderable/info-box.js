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
import a from"@typo3/install/renderable/severity.js";import{topDocument as v}from"@typo3/core/utility/top-window.js";import{property as f,customElement as d}from"lit/decorators.js";import{LitElement as m,nothing as y,html as u}from"lit";var s=function(c,e,o,n){var r=arguments.length,t=r<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(c,e,o,n);else for(var p=c.length-1;p>=0;p--)(l=c[p])&&(t=(r<3?l(t):r>3?l(e,o,t):l(e,o))||t);return r>3&&t&&Object.defineProperty(e,o,t),t};let i=class extends m{static create(e,o,n=""){const t=v.createElement("typo3-install-infobox");return t.severity=e,t.subject=o,n&&(t.content=n),t}createRenderRoot(){return this}render(){let e=y;return this.content&&(e=u`<div class=callout-body>${this.content}</div>`),u`<div class="t3js-infobox callout callout-sm callout-${a.getCssClass(this.severity)}"><div class=callout-content><div class=callout-title>${this.subject}</div>${e}</div></div>`}};s([f({type:Number})],i.prototype,"severity",void 0),s([f({type:String})],i.prototype,"subject",void 0),s([f({type:String})],i.prototype,"content",void 0),i=s([d("typo3-install-infobox")],i);export{i as InfoBox};
