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
var __decorate=function(t,e,o,r){var a,l=arguments.length,i=l<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(t,e,o,r);else for(var n=t.length-1;n>=0;n--)(a=t[n])&&(i=(l<3?a(i):l>3?a(e,o,i):a(e,o))||i);return l>3&&i&&Object.defineProperty(e,o,i),i};import{html,css,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import Modal from"@typo3/backend/modal.js";let MfaTotpUrlButton=class extends LitElement{constructor(){super(),this.addEventListener("click",(t=>{t.preventDefault(),this.showTotpAuthUrlModal()})),this.addEventListener("keydown",(t=>{"Enter"!==t.key&&" "!==t.key||(t.preventDefault(),this.showTotpAuthUrlModal())}))}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return html`<slot></slot>`}showTotpAuthUrlModal(){Modal.advanced({title:this.modalTitle,content:html`
        <p>${this.modalDescription}</p>
        <pre>${this.modalUrl}</pre>
      `,buttons:[{trigger:()=>Modal.dismiss(),text:this.buttonOk||"OK",active:!0,btnClass:"btn-default",name:"ok"}]})}};MfaTotpUrlButton.styles=[css`:host { cursor: pointer; appearance: button; }`],__decorate([property({type:String,attribute:"data-url"})],MfaTotpUrlButton.prototype,"modalUrl",void 0),__decorate([property({type:String,attribute:"data-title"})],MfaTotpUrlButton.prototype,"modalTitle",void 0),__decorate([property({type:String,attribute:"data-description"})],MfaTotpUrlButton.prototype,"modalDescription",void 0),__decorate([property({type:String,attribute:"data-button-ok"})],MfaTotpUrlButton.prototype,"buttonOk",void 0),MfaTotpUrlButton=__decorate([customElement("typo3-mfa-totp-url-info-button")],MfaTotpUrlButton);export{MfaTotpUrlButton};