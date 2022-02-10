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
var Selectors,__decorate=function(t,e,o,r){var l,p=arguments.length,n=p<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,o,r);else for(var i=t.length-1;i>=0;i--)(l=t[i])&&(n=(p<3?l(n):p>3?l(e,o,n):l(e,o))||n);return p>3&&n&&Object.defineProperty(e,o,n),n};import{render,html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import Modal from"@typo3/backend/modal.js";!function(t){t.modalBody=".t3js-modal-body"}(Selectors||(Selectors={}));let MfaTotpUrlButton=class extends LitElement{constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.showTotpAuthUrlModal()})}render(){return html`<slot></slot>`}showTotpAuthUrlModal(){Modal.advanced({title:this.title,content:"",buttons:[{trigger:()=>Modal.dismiss(),text:this.ok||"OK",active:!0,btnClass:"btn-default",name:"ok"}],callback:t=>{render(html`
            <p>${this.description}</p>
            <pre>${this.url}</pre>
          `,t[0].querySelector(Selectors.modalBody))}})}};__decorate([property({type:String})],MfaTotpUrlButton.prototype,"url",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"title",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"description",void 0),__decorate([property({type:String})],MfaTotpUrlButton.prototype,"ok",void 0),MfaTotpUrlButton=__decorate([customElement("typo3-mfa-totp-url-info-button")],MfaTotpUrlButton);