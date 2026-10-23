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
import{html as m}from"lit";import{property as u,customElement as c}from"lit/decorators.js";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import s from"@typo3/backend/modal.js";var i=function(l,e,r,n){var p=arguments.length,t=p<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(l,e,r,n);else for(var d=l.length-1;d>=0;d--)(a=l[d])&&(t=(p<3?a(t):p>3?a(e,r,t):a(e,r))||t);return p>3&&t&&Object.defineProperty(e,r,t),t};let o=class extends f{constructor(){super(...arguments),this.modalUrl="",this.modalTitle="",this.modalDescription="",this.buttonOk=""}buttonActivated(){this.showTotpAuthUrlModal()}showTotpAuthUrlModal(){s.advanced({title:this.modalTitle,content:m`<p>${this.modalDescription}</p><pre>${this.modalUrl}</pre>`,buttons:[{trigger:()=>s.dismiss(),text:this.buttonOk||"OK",active:!0,btnClass:"btn-default",name:"ok"}]})}};i([u({type:String,attribute:"data-url"})],o.prototype,"modalUrl",void 0),i([u({type:String,attribute:"data-title"})],o.prototype,"modalTitle",void 0),i([u({type:String,attribute:"data-description"})],o.prototype,"modalDescription",void 0),i([u({type:String,attribute:"data-button-ok"})],o.prototype,"buttonOk",void 0),o=i([c("typo3-mfa-totp-url-info-button")],o);export{o as MfaTotpUrlButton};
