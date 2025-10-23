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
import{html as f}from"lit";import{property as u,customElement as s}from"lit/decorators.js";import{PseudoButtonLitElement as c}from"@typo3/backend/element/pseudo-button.js";import m from"@typo3/backend/modal.js";var i=function(n,e,r,p){var l=arguments.length,t=l<3?e:p===null?p=Object.getOwnPropertyDescriptor(e,r):p,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,r,p);else for(var d=n.length-1;d>=0;d--)(a=n[d])&&(t=(l<3?a(t):l>3?a(e,r,t):a(e,r))||t);return l>3&&t&&Object.defineProperty(e,r,t),t};let o=class extends c{buttonActivated(){this.showTotpAuthUrlModal()}showTotpAuthUrlModal(){m.advanced({title:this.modalTitle,content:f`<p>${this.modalDescription}</p><pre>${this.modalUrl}</pre>`,buttons:[{trigger:()=>m.dismiss(),text:this.buttonOk||"OK",active:!0,btnClass:"btn-default",name:"ok"}]})}};i([u({type:String,attribute:"data-url"})],o.prototype,"modalUrl",void 0),i([u({type:String,attribute:"data-title"})],o.prototype,"modalTitle",void 0),i([u({type:String,attribute:"data-description"})],o.prototype,"modalDescription",void 0),i([u({type:String,attribute:"data-button-ok"})],o.prototype,"buttonOk",void 0),o=i([s("typo3-mfa-totp-url-info-button")],o);export{o as MfaTotpUrlButton};
