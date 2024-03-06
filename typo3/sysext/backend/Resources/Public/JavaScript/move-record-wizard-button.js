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
var __decorate=function(e,t,r,o){var i,n=arguments.length,d=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)d=Reflect.decorate(e,t,r,o);else for(var s=e.length-1;s>=0;s--)(i=e[s])&&(d=(n<3?i(d):n>3?i(t,r,d):i(t,r))||d);return n>3&&d&&Object.defineProperty(t,r,d),d};import{customElement,property}from"lit/decorators.js";import{html,css,LitElement}from"lit";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import{KeyTypesEnum}from"@typo3/backend/enum/key-types.js";let MoveRecordWizardButton=class extends LitElement{connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("click",this.triggerWizard),this.addEventListener("keydown",this.triggerWizard)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this.triggerWizard),this.removeEventListener("keydown",this.triggerWizard)}render(){return html`<slot></slot>`}triggerWizard(e){e instanceof KeyboardEvent&&e.key!==KeyTypesEnum.ENTER&&e.key!==KeyTypesEnum.SPACE||e.preventDefault(),this.renderWizard()}renderWizard(){this.url&&Modal.advanced({content:this.url,title:this.subject,severity:SeverityEnum.notice,size:Modal.sizes.large,type:Modal.types.iframe})}};MoveRecordWizardButton.styles=[css`:host { cursor: pointer; appearance: button; }`],__decorate([property({type:String})],MoveRecordWizardButton.prototype,"url",void 0),__decorate([property({type:String})],MoveRecordWizardButton.prototype,"subject",void 0),MoveRecordWizardButton=__decorate([customElement("typo3-move-record-wizard-button")],MoveRecordWizardButton);export{MoveRecordWizardButton};