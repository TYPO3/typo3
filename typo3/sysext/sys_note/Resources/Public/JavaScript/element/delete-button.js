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
import{property as r,customElement as u}from"lit/decorators.js";import{LitElement as m,css as b,html as f}from"lit";import p from"@typo3/backend/modal.js";import{SeverityEnum as h}from"@typo3/backend/enum/severity.js";import y from"@typo3/backend/action-button/deferred-action.js";import v from"@typo3/backend/ajax-data-handler.js";import C from"@typo3/backend/viewport.js";var n=function(l,t,i,s){var a=arguments.length,o=a<3?t:s===null?s=Object.getOwnPropertyDescriptor(t,i):s,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(l,t,i,s);else for(var d=l.length-1;d>=0;d--)(c=l[d])&&(o=(a<3?c(o):a>3?c(t,i,o):c(t,i))||o);return a>3&&o&&Object.defineProperty(t,i,o),o};let e=class extends m{static{this.styles=[b`:host{cursor:pointer;appearance:button}`]}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("click",this.showConfirmationModal)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this.showConfirmationModal)}render(){return f`<slot></slot>`}showConfirmationModal(){p.advanced({content:this.modalContent,title:this.modalTitle,severity:h.warning,size:p.sizes.small,buttons:[{text:this.cancelButtonLabel||"Close",btnClass:"btn-default",trigger:function(){p.dismiss()}},{text:this.okButtonLabel||"OK",btnClass:"btn-warning",action:new y(async()=>{await this.deleteRecord()})}]})}async deleteRecord(){const t=v.process(`cmd[sys_note][${this.uid}][delete]=1`);return t.then(()=>{C.ContentContainer.setUrl(this.returnUrl)}),t}};n([r({type:Number})],e.prototype,"uid",void 0),n([r({type:String,attribute:"return-url"})],e.prototype,"returnUrl",void 0),n([r({type:String,attribute:"modal-title"})],e.prototype,"modalTitle",void 0),n([r({type:String,attribute:"modal-content"})],e.prototype,"modalContent",void 0),n([r({type:String,attribute:"modal-button-ok"})],e.prototype,"okButtonLabel",void 0),n([r({type:String,attribute:"modal-button-cancel"})],e.prototype,"cancelButtonLabel",void 0),e=n([u("typo3-sysnote-delete-button")],e);export{e as DeleteButton};
