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
import{property as r,customElement as c}from"lit/decorators.js";import{PseudoButtonLitElement as m}from"@typo3/backend/element/pseudo-button.js";import d from"@typo3/backend/modal.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import f from"@typo3/backend/action-button/deferred-action.js";import y from"@typo3/backend/ajax-data-handler.js";import v from"@typo3/backend/viewport.js";var n=function(l,t,i,a){var p=arguments.length,o=p<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,i):a,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(l,t,i,a);else for(var u=l.length-1;u>=0;u--)(s=l[u])&&(o=(p<3?s(o):p>3?s(t,i,o):s(t,i))||o);return p>3&&o&&Object.defineProperty(t,i,o),o};let e=class extends m{buttonActivated(){d.advanced({content:this.modalContent,title:this.modalTitle,severity:b.warning,size:d.sizes.small,buttons:[{text:this.cancelButtonLabel||"Close",btnClass:"btn-default",trigger:function(){d.dismiss()}},{text:this.okButtonLabel||"OK",btnClass:"btn-warning",action:new f(async()=>{await this.deleteRecord()})}]})}async deleteRecord(){const t=y.process(`cmd[sys_note][${this.uid}][delete]=1`);return t.then(()=>{v.ContentContainer.setUrl(this.returnUrl)}),t}};n([r({type:Number})],e.prototype,"uid",void 0),n([r({type:String,attribute:"return-url"})],e.prototype,"returnUrl",void 0),n([r({type:String,attribute:"modal-title"})],e.prototype,"modalTitle",void 0),n([r({type:String,attribute:"modal-content"})],e.prototype,"modalContent",void 0),n([r({type:String,attribute:"modal-button-ok"})],e.prototype,"okButtonLabel",void 0),n([r({type:String,attribute:"modal-button-cancel"})],e.prototype,"cancelButtonLabel",void 0),e=n([c("typo3-sysnote-delete-button")],e);export{e as DeleteButton};
