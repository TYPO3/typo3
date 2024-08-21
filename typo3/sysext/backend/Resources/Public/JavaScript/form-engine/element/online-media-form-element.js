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
var __decorate=function(e,t,r,o){var n,i=arguments.length,l=i<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(l=(i<3?n(l):i>3?n(t,r,l):n(t,r))||l);return i>3&&l&&Object.defineProperty(t,r,l),l};import{customElement,property}from"lit/decorators.js";import{LitElement,html}from"lit";let OnlineMediaFormElement=class extends LitElement{createRenderRoot(){return this}render(){return html`
      <form @submit="${this.dispatchSubmitEvent}">
        <div class="form-control-wrap">
          <input type="text" class="form-control" name="online-media-url" placeholder="${this.placeholder}" required>
          <div class="form-text">
            ${this.allowedExtensionsHelpText}<br>
            <ul class="badge-list">
            ${this.allowedExtensions.split(",").map((e=>html`
              <li><span class="badge badge-success">${e.trim().toUpperCase()}</span></li>
            `))}
            </ul>
          </div>
        </div>
      </form>
    `}dispatchSubmitEvent(e){e.preventDefault();const t=new FormData(e.target),r=Object.fromEntries(t);this.dispatchEvent(new CustomEvent("typo3:formengine:online-media-added",{detail:r}))}};__decorate([property({type:String})],OnlineMediaFormElement.prototype,"placeholder",void 0),__decorate([property({type:String,attribute:"help-text"})],OnlineMediaFormElement.prototype,"allowedExtensionsHelpText",void 0),__decorate([property({type:String,attribute:"extensions"})],OnlineMediaFormElement.prototype,"allowedExtensions",void 0),OnlineMediaFormElement=__decorate([customElement("typo3-backend-formengine-online-media-form")],OnlineMediaFormElement);export{OnlineMediaFormElement};