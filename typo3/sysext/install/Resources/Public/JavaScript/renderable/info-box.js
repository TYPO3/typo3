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
import d from"@typo3/install/renderable/severity.js";import{property as f,customElement as u}from"lit/decorators.js";import{LitElement as m,nothing as v,html as p}from"lit";var s=function(l,t,n,i){var c=arguments.length,e=c<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,n):i,o;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(l,t,n,i);else for(var a=l.length-1;a>=0;a--)(o=l[a])&&(e=(c<3?o(e):c>3?o(t,n,e):o(t,n))||e);return c>3&&e&&Object.defineProperty(t,n,e),e};let r=class extends m{static create(t,n,i=""){const o=(window.location!==window.parent.location?window.parent.document:document).createElement("typo3-install-infobox");return o.severity=t,o.subject=n,i&&(o.content=i),o}createRenderRoot(){return this}render(){let t=v;return this.content&&(t=p`<div class=callout-body>${this.content}</div>`),p`<div class="t3js-infobox callout callout-sm callout-${d.getCssClass(this.severity)}"><div class=callout-content><div class=callout-title>${this.subject}</div>${t}</div></div>`}};s([f({type:Number})],r.prototype,"severity",void 0),s([f({type:String})],r.prototype,"subject",void 0),s([f({type:String})],r.prototype,"content",void 0),r=s([u("typo3-install-infobox")],r);export{r as InfoBox};
