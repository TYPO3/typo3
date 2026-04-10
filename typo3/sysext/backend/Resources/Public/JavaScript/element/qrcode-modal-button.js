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
import{property as d,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as u}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/modal.js";import{html as f}from"lit";import{topLevelModuleImport as h}from"@typo3/backend/utility/top-level-module-import.js";import{topWindow as w}from"@typo3/core/utility/top-window.js";import y from"~labels/core.mod_web_list";var r=function(n,t,e,i){var a=arguments.length,o=a<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,e):i,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(n,t,e,i);else for(var p=n.length-1;p>=0;p--)(s=n[p])&&(o=(a<3?s(o):a>3?s(t,e,o):s(t,e))||o);return a>3&&o&&Object.defineProperty(t,e,o),o};let l=class extends u{constructor(){super(...arguments),this.showUrl=!1,this.showDownload=!1}buttonActivated(){this.modalOpen()}async loadModuleFrameAgnostic(t){w!==window?await h(t):await import(t)}async modalOpen(){await this.loadModuleFrameAgnostic("@typo3/backend/element/qrcode-element.js"),c.advanced({title:this.modalTitle||"QR Code",size:c.sizes.small,content:f`<div class=text-center><typo3-qrcode class=text-start content=${this.content} size=large ?show-download=${this.showDownload} ?show-url=${this.showUrl}></typo3-qrcode></div>`,buttons:[{text:y.get("button.close"),name:"close",trigger:function(t,e){e.hideModal()}}]})}};r([d({type:String,attribute:"modal-title"})],l.prototype,"modalTitle",void 0),r([d({type:String})],l.prototype,"content",void 0),r([d({type:Boolean,attribute:"show-url"})],l.prototype,"showUrl",void 0),r([d({type:Boolean,attribute:"show-download"})],l.prototype,"showDownload",void 0),l=r([m("typo3-qrcode-modal-button")],l);export{l as QrCodeModalButton};
