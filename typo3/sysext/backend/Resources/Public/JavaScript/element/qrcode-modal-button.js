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
import{property as d,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as u}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/modal.js";import{html as h}from"lit";import{topLevelModuleImport as f}from"@typo3/backend/utility/top-level-module-import.js";import{topWindow as w}from"@typo3/core/utility/top-window.js";import y from"~labels/core.mod_web_list";var n=function(r,t,e,i){var s=arguments.length,o=s<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,e):i,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(r,t,e,i);else for(var p=r.length-1;p>=0;p--)(a=r[p])&&(o=(s<3?a(o):s>3?a(t,e,o):a(t,e))||o);return s>3&&o&&Object.defineProperty(t,e,o),o};let l=class extends u{constructor(){super(...arguments),this.modalTitle="",this.content="",this.showUrl=!1,this.showDownload=!1}buttonActivated(){this.modalOpen()}async loadModuleFrameAgnostic(t){w!==window?await f(t):await import(t)}async modalOpen(){await this.loadModuleFrameAgnostic("@typo3/backend/element/qrcode-element.js"),c.advanced({title:this.modalTitle||"QR Code",size:c.sizes.small,content:h`<div class=text-center><typo3-qrcode class=text-start content=${this.content} size=large ?show-download=${this.showDownload} ?show-url=${this.showUrl}></typo3-qrcode></div>`,buttons:[{text:y.get("button.close"),name:"close",trigger:function(t,e){e.hideModal()}}]})}};n([d({type:String,attribute:"modal-title"})],l.prototype,"modalTitle",void 0),n([d({type:String})],l.prototype,"content",void 0),n([d({type:Boolean,attribute:"show-url"})],l.prototype,"showUrl",void 0),n([d({type:Boolean,attribute:"show-download"})],l.prototype,"showDownload",void 0),l=n([m("typo3-qrcode-modal-button")],l);export{l as QrCodeModalButton};
