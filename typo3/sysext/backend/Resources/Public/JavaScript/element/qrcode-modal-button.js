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
import{property as d,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as u}from"@typo3/backend/element/pseudo-button.js";import p from"@typo3/backend/modal.js";import{html as w}from"lit";import{topLevelModuleImport as f}from"@typo3/backend/utility/top-level-module-import.js";var l=function(r,t,o,i){var a=arguments.length,e=a<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,o):i,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(r,t,o,i);else for(var c=r.length-1;c>=0;c--)(s=r[c])&&(e=(a<3?s(e):a>3?s(t,o,e):s(t,o))||e);return a>3&&e&&Object.defineProperty(t,o,e),e};let n=class extends u{constructor(){super(...arguments),this.showUrl=!1,this.showDownload=!1}buttonActivated(){this.modalOpen()}async loadModuleFrameAgnostic(t){window.location!==window.parent.location?await f(t):await import(t)}async modalOpen(){await this.loadModuleFrameAgnostic("@typo3/backend/element/qrcode-element.js"),p.advanced({title:this.modalTitle||"QR Code",size:p.sizes.small,content:w`<div class=text-center><typo3-qrcode class=text-start content=${this.content} size=large ?show-download=${this.showDownload} ?show-url=${this.showUrl}></typo3-qrcode></div>`,buttons:[{text:TYPO3.lang["button.close"]||"Close",name:"close",trigger:function(t,o){o.hideModal()}}]})}};l([d({type:String,attribute:"modal-title"})],n.prototype,"modalTitle",void 0),l([d({type:String})],n.prototype,"content",void 0),l([d({type:Boolean,attribute:"show-url"})],n.prototype,"showUrl",void 0),l([d({type:Boolean,attribute:"show-download"})],n.prototype,"showDownload",void 0),n=l([m("typo3-qrcode-modal-button")],n);export{n as QrCodeModalButton};
