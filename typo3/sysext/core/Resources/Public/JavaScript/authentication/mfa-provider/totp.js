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
import{LitElement as h,css as f,html as d}from"lit";import{property as p,customElement as b}from"lit/decorators.js";import c from"@typo3/backend/modal.js";var i=function(n,t,r,l){var a=arguments.length,e=a<3?t:l===null?l=Object.getOwnPropertyDescriptor(t,r):l,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,r,l);else for(var u=n.length-1;u>=0;u--)(s=n[u])&&(e=(a<3?s(e):a>3?s(t,r,e):s(t,r))||e);return a>3&&e&&Object.defineProperty(t,r,e),e};let o=class extends h{static{this.styles=[f`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.showTotpAuthUrlModal()}),this.addEventListener("keydown",t=>{(t.key==="Enter"||t.key===" ")&&(t.preventDefault(),this.showTotpAuthUrlModal())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return d`<slot></slot>`}showTotpAuthUrlModal(){c.advanced({title:this.modalTitle,content:d`<p>${this.modalDescription}</p><pre>${this.modalUrl}</pre>`,buttons:[{trigger:()=>c.dismiss(),text:this.buttonOk||"OK",active:!0,btnClass:"btn-default",name:"ok"}]})}};i([p({type:String,attribute:"data-url"})],o.prototype,"modalUrl",void 0),i([p({type:String,attribute:"data-title"})],o.prototype,"modalTitle",void 0),i([p({type:String,attribute:"data-description"})],o.prototype,"modalDescription",void 0),i([p({type:String,attribute:"data-button-ok"})],o.prototype,"buttonOk",void 0),o=i([b("typo3-mfa-totp-url-info-button")],o);export{o as MfaTotpUrlButton};
