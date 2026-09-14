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
import{property as d,customElement as c}from"lit/decorators.js";import{LitElement as f,html as m}from"lit";var s=function(i,e,o,n){var l=arguments.length,t=l<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,o,n);else for(var p=i.length-1;p>=0;p--)(a=i[p])&&(t=(l<3?a(t):l>3?a(e,o,t):a(e,o))||t);return l>3&&t&&Object.defineProperty(e,o,t),t};let r=class extends f{createRenderRoot(){return this}render(){return m`<form @submit=${this.dispatchSubmitEvent}><div class=form-control-wrap><input type=text class=form-control name=online-media-url placeholder=${this.placeholder} required><div class=form-text>${this.allowedExtensionsHelpText}<br><ul class=badge-list>${this.allowedExtensions.split(",").map(e=>m`<li><span class="badge badge-secondary">${e.trim().toLowerCase()}</span></li>`)}</ul></div></div></form>`}dispatchSubmitEvent(e){e.preventDefault();const o=new FormData(e.target),n=Object.fromEntries(o);this.dispatchEvent(new CustomEvent("typo3:formengine:online-media-added",{detail:n}))}};s([d({type:String})],r.prototype,"placeholder",void 0),s([d({type:String,attribute:"help-text"})],r.prototype,"allowedExtensionsHelpText",void 0),s([d({type:String,attribute:"extensions"})],r.prototype,"allowedExtensions",void 0),r=s([c("typo3-backend-formengine-online-media-form")],r);export{r as OnlineMediaFormElement};
