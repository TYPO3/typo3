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
import{LitElement as h,css as p,nothing as g,html as f}from"lit";import{property as m,customElement as u}from"lit/decorators.js";var o=function(s,t,e,l){var a=arguments.length,i=a<3?t:l===null?l=Object.getOwnPropertyDescriptor(t,e):l,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")i=Reflect.decorate(s,t,e,l);else for(var c=s.length-1;c>=0;c--)(n=s[c])&&(i=(a<3?n(i):a>3?n(t,e,i):n(t,e))||i);return a>3&&i&&Object.defineProperty(t,e,i),i};let r=class extends h{constructor(){super(...arguments),this.alt="",this.image="",this.welcomeImage="",this.fallback=""}static{this.styles=p`img{display:block;width:100%;height:auto}`}render(){if(!this.image&&!this.fallback)return g;const t=this.welcomeImage||this.image||this.fallback;return f`<img alt=${this.alt} src=${t} @error=${t!==this.fallback?this.onError:g}>`}onError(t){const e=t.target;this.image.length&&e.getAttribute("src")===this.welcomeImage?e.setAttribute("src",this.image):this.fallback.length&&e.setAttribute("src",this.fallback)}};o([m({type:String})],r.prototype,"alt",void 0),o([m({type:String})],r.prototype,"image",void 0),o([m({type:String})],r.prototype,"welcomeImage",void 0),o([m({type:String})],r.prototype,"fallback",void 0),r=o([u("typo3-extensionmanager-distribution-image")],r);export{r as DistributionImage};
