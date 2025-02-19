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
import{property as d,customElement as c}from"lit/decorators.js";import{LitElement as f,html as m}from"lit";var a=function(i,e,n,o){var l=arguments.length,t=l<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,n):o,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,n,o);else for(var p=i.length-1;p>=0;p--)(s=i[p])&&(t=(l<3?s(t):l>3?s(e,n,t):s(e,n))||t);return l>3&&t&&Object.defineProperty(e,n,t),t};let r=class extends f{createRenderRoot(){return this}render(){return m`<form @submit=${this.dispatchSubmitEvent}><div class=form-control-wrap><input type=text class=form-control name=online-media-url placeholder=${this.placeholder} required><div class=form-text>${this.allowedExtensionsHelpText}<br><ul class=badge-list>${this.allowedExtensions.split(",").map(e=>m`<li><span class="badge badge-success">${e.trim().toUpperCase()}</span></li>`)}</ul></div></div></form>`}dispatchSubmitEvent(e){e.preventDefault();const n=new FormData(e.target),o=Object.fromEntries(n);this.dispatchEvent(new CustomEvent("typo3:formengine:online-media-added",{detail:o}))}};a([d({type:String})],r.prototype,"placeholder",void 0),a([d({type:String,attribute:"help-text"})],r.prototype,"allowedExtensionsHelpText",void 0),a([d({type:String,attribute:"extensions"})],r.prototype,"allowedExtensions",void 0),r=a([c("typo3-backend-formengine-online-media-form")],r);export{r as OnlineMediaFormElement};
