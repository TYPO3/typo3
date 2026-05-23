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
import{property as p,customElement as c}from"lit/decorators.js";import{LitElement as f,html as m}from"lit";var s=function(r,e,o,n){var l=arguments.length,t=l<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,o,n);else for(var d=r.length-1;d>=0;d--)(a=r[d])&&(t=(l<3?a(t):l>3?a(e,o,t):a(e,o))||t);return l>3&&t&&Object.defineProperty(e,o,t),t};let i=class extends f{createRenderRoot(){return this}render(){return m`<form id=online-media-form @submit=${this.dispatchSubmitEvent}><div class=form-control-wrap><input type=text class=form-control name=online-media-url placeholder=${this.placeholder} required autofocus><div class=form-text>${this.allowedExtensionsHelpText}<br><ul class=badge-list>${this.allowedExtensions.split(",").map(e=>m`<li><span class="badge badge-secondary">${e.trim().toLowerCase()}</span></li>`)}</ul></div></div></form>`}dispatchSubmitEvent(e){e.preventDefault();const o=new FormData(e.target),n=Object.fromEntries(o);this.dispatchEvent(new CustomEvent("typo3:formengine:online-media-added",{detail:n}))}};s([p({type:String})],i.prototype,"placeholder",void 0),s([p({type:String,attribute:"help-text"})],i.prototype,"allowedExtensionsHelpText",void 0),s([p({type:String,attribute:"extensions"})],i.prototype,"allowedExtensions",void 0),i=s([c("typo3-backend-formengine-online-media-form")],i);export{i as OnlineMediaFormElement};
