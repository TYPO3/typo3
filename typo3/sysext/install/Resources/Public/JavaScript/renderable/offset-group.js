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
import{LitElement as d,html as a}from"lit";import{property as p,customElement as c}from"lit/decorators.js";var n=function(r,e,o,s){var i=arguments.length,t=i<3?e:s===null?s=Object.getOwnPropertyDescriptor(e,o):s,f;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,o,s);else for(var u=r.length-1;u>=0;u--)(f=r[u])&&(t=(i<3?f(t):i>3?f(e,o,t):f(e,o))||t);return i>3&&t&&Object.defineProperty(e,o,t),t};let l=class extends d{constructor(){super(...arguments),this.offsetId=null,this.values=null}createRenderRoot(){return this}render(){return a`<div class=form-multigroup-wrap><div class=form-multigroup-item><div class=input-group><div class=input-group-text>x</div><input id=${this.offsetId}_offset_x class="form-control t3js-emconf-offsetfield" data-target=#${this.offsetId} value=${this.values[0]?.trim()}></div></div><div class=form-multigroup-item><div class=input-group><div class=input-group-text>y</div><input id=${this.offsetId}_offset_y class="form-control t3js-emconf-offsetfield" data-target=#${this.offsetId} value=${this.values[1]?.trim()}></div></div></div>`}};n([p({type:String})],l.prototype,"offsetId",void 0),n([p({type:Array})],l.prototype,"values",void 0),l=n([c("typo3-install-offset-group")],l);
