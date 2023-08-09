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
var __decorate=function(t,e,r,n){var o,i=arguments.length,a=i<3?e:null===n?n=Object.getOwnPropertyDescriptor(e,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(t,e,r,n);else for(var d=t.length-1;d>=0;d--)(o=t[d])&&(a=(i<3?o(a):i>3?o(e,r,a):o(e,r))||a);return i>3&&a&&Object.defineProperty(e,r,a),a};import{customElement,property}from"lit/decorators.js";import{html,css,LitElement}from"lit";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import"@typo3/backend/new-content-element-wizard.js";let NewContentElementWizardButton=class extends LitElement{constructor(){super(),this.addEventListener("click",(t=>{t.preventDefault(),this.renderWizard()})),this.addEventListener("keydown",(t=>{"Enter"!==t.key&&" "!==t.key||(t.preventDefault(),this.renderWizard())}))}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return html`<slot></slot>`}renderWizard(){this.url&&Modal.advanced({content:this.url,title:this.subject,severity:SeverityEnum.notice,size:Modal.sizes.large,type:Modal.types.ajax})}};NewContentElementWizardButton.styles=[css`:host { cursor: pointer; appearance: button; }`],__decorate([property({type:String})],NewContentElementWizardButton.prototype,"url",void 0),__decorate([property({type:String})],NewContentElementWizardButton.prototype,"subject",void 0),NewContentElementWizardButton=__decorate([customElement("typo3-backend-new-content-element-wizard-button")],NewContentElementWizardButton);export{NewContentElementWizardButton};