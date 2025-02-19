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
import{LitElement as p,css as h,nothing as g,html as f}from"lit";import{property as m,customElement as b}from"lit/decorators.js";var o=function(l,t,e,n){var s=arguments.length,i=s<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,e):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")i=Reflect.decorate(l,t,e,n);else for(var c=l.length-1;c>=0;c--)(a=l[c])&&(i=(s<3?a(i):s>3?a(t,e,i):a(t,e))||i);return s>3&&i&&Object.defineProperty(t,e,i),i};let r=class extends p{static{this.styles=h`img{display:block;width:100%;height:auto}`}render(){if(!this.image&&!this.fallback)return g;const t=this.welcomeImage||this.image||this.fallback;return f`<img alt=${this.alt} src=${t} @error=${t!==this.fallback?this.onError:g}>`}onError(t){const e=t.target;this.image.length&&e.getAttribute("src")===this.welcomeImage?e.setAttribute("src",this.image):this.fallback.length&&e.setAttribute("src",this.fallback)}};o([m({type:String})],r.prototype,"alt",void 0),o([m({type:String})],r.prototype,"image",void 0),o([m({type:String})],r.prototype,"welcomeImage",void 0),o([m({type:String})],r.prototype,"fallback",void 0),r=o([b("typo3-extensionmanager-distribution-image")],r);export{r as DistributionImage};
