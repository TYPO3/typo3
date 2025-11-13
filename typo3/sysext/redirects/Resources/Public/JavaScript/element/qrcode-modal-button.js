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
import{property as p,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/modal.js";import{html as u}from"lit";import{topLevelModuleImport as y}from"@typo3/backend/utility/top-level-module-import.js";var s=function(l,t,e,i){var a=arguments.length,o=a<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,e):i,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(l,t,e,i);else for(var d=l.length-1;d>=0;d--)(r=l[d])&&(o=(a<3?r(o):a>3?r(t,e,o):r(t,e))||o);return a>3&&o&&Object.defineProperty(t,e,o),o};let n=class extends f{buttonActivated(){this.modalOpen()}async loadModuleFrameAgnostic(t){window.location!==window.parent.location?await y(t):await import(t)}async modalOpen(){await this.loadModuleFrameAgnostic("@typo3/backend/element/qrcode-element.js"),c.advanced({type:c.types.template,title:this.modalTitle||"QR Code",size:c.sizes.small,callback:t=>{t.setContent(u`<div class=text-center><typo3-qrcode class=text-start content=${this.content} size=large show-download></typo3-qrcode></div>`)},buttons:[{text:TYPO3.lang["button.close"]||"Close",name:"close",trigger:function(t,e){e.hideModal()}}]})}};s([p({type:String,attribute:"modal-title"})],n.prototype,"modalTitle",void 0),s([p({type:String})],n.prototype,"content",void 0),n=s([m("typo3-qrcode-modal-button")],n);export{n as QrCodeModalButton};
