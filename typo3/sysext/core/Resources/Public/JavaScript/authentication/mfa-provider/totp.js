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
var __decorate=function(t,e,o,r){var p,l=arguments.length,n=l<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,o,r);else for(var i=t.length-1;i>=0;i--)(p=t[i])&&(n=(l<3?p(n):l>3?p(e,o,n):p(e,o))||n);return l>3&&n&&Object.defineProperty(e,o,n),n};import{html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import Modal from"@typo3/backend/modal.js";let MfaTotpUrlButton=class extends LitElement{constructor(){super(),this.addEventListener("click",(t=>{t.preventDefault(),this.showTotpAuthUrlModal()}))}render(){return html`<slot></slot>`}showTotpAuthUrlModal(){Modal.advanced({title:this.title,content:html`
        <p>${this.description}</p>
        <pre>${this.url}</pre>
      `,buttons:[{trigger:()=>Modal.dismiss(),text:this.ok||"OK",active:!0,btnClass:"btn-default",name:"ok"}]})}};__decorate([property({type:String})],MfaTotpUrlButton.prototype,"url",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"title",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"description",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"ok",void 0),MfaTotpUrlButton=__decorate([customElement("typo3-mfa-totp-url-info-button")],MfaTotpUrlButton);export{MfaTotpUrlButton};