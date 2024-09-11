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
var __decorate=function(t,e,r,o){var i,n=arguments.length,s=n<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,r,o);else for(var a=t.length-1;a>=0;a--)(i=t[a])&&(s=(n<3?i(s):n>3?i(e,r,s):i(e,r))||s);return n>3&&s&&Object.defineProperty(e,r,s),s};import{customElement,property}from"lit/decorators.js";import{html,css,LitElement}from"lit";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import{KeyTypesEnum}from"@typo3/backend/enum/key-types.js";let DispatchModalButton=class extends LitElement{connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("click",this.triggerWizard),this.addEventListener("keydown",this.triggerWizard)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this.triggerWizard),this.removeEventListener("keydown",this.triggerWizard)}render(){return html`<slot></slot>`}triggerWizard(t){t instanceof KeyboardEvent&&t.key!==KeyTypesEnum.ENTER&&t.key!==KeyTypesEnum.SPACE||t.preventDefault(),this.renderWizard()}renderWizard(){this.url&&Modal.advanced({content:this.url,title:this.subject,severity:SeverityEnum.notice,size:Modal.sizes.large,type:Modal.types.iframe})}};DispatchModalButton.styles=[css`:host { cursor: pointer; appearance: button; }`],__decorate([property({type:String})],DispatchModalButton.prototype,"url",void 0),__decorate([property({type:String})],DispatchModalButton.prototype,"subject",void 0),DispatchModalButton=__decorate([customElement("typo3-backend-dispatch-modal-button")],DispatchModalButton);export{DispatchModalButton};