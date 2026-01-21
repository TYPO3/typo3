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
import{property as c,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as u}from"@typo3/backend/element/pseudo-button.js";import p from"@typo3/backend/modal.js";import{html as f}from"lit";import{topLevelModuleImport as h}from"@typo3/backend/utility/top-level-module-import.js";var s=function(r,t,o,l){var i=arguments.length,e=i<3?t:l===null?l=Object.getOwnPropertyDescriptor(t,o):l,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(r,t,o,l);else for(var d=r.length-1;d>=0;d--)(a=r[d])&&(e=(i<3?a(e):i>3?a(t,o,e):a(t,o))||e);return i>3&&e&&Object.defineProperty(t,o,e),e};let n=class extends u{constructor(){super(...arguments),this.showUrl=!1}buttonActivated(){this.modalOpen()}async loadModuleFrameAgnostic(t){window.location!==window.parent.location?await h(t):await import(t)}async modalOpen(){await this.loadModuleFrameAgnostic("@typo3/backend/element/qrcode-element.js"),p.advanced({title:this.modalTitle||"QR Code",size:p.sizes.small,content:f`<div class=text-center><typo3-qrcode class=text-start content=${this.content} size=large show-download ?show-url=${this.showUrl}></typo3-qrcode></div>`,buttons:[{text:TYPO3.lang["button.close"]||"Close",name:"close",trigger:function(t,o){o.hideModal()}}]})}};s([c({type:String,attribute:"modal-title"})],n.prototype,"modalTitle",void 0),s([c({type:String})],n.prototype,"content",void 0),s([c({type:Boolean,attribute:"show-url"})],n.prototype,"showUrl",void 0),n=s([m("typo3-qrcode-modal-button")],n);export{n as QrCodeModalButton};
