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
var NewContentElementWizardButton_1,__decorate=function(e,t,n,o){var r,i=arguments.length,a=i<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,n):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(e,t,n,o);else for(var l=e.length-1;l>=0;l--)(r=e[l])&&(a=(i<3?r(a):i>3?r(t,n,a):r(t,n))||a);return i>3&&a&&Object.defineProperty(t,n,a),a};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import{NewContentElementWizard}from"@typo3/backend/new-content-element-wizard.js";let NewContentElementWizardButton=NewContentElementWizardButton_1=class extends LitElement{constructor(){super(),this.addEventListener("click",(e=>{e.preventDefault(),this.renderWizard()}))}static handleModalContentLoaded(e){e&&e.querySelector(".t3-new-content-element-wizard-inner")&&new NewContentElementWizard(e)}render(){return html`<slot></slot>`}renderWizard(){if(!this.url)return;const e=Modal.advanced({content:this.url,title:this.title,severity:SeverityEnum.notice,size:Modal.sizes.medium,type:Modal.types.ajax,ajaxCallback:()=>NewContentElementWizardButton_1.handleModalContentLoaded(e)})}};__decorate([property({type:String})],NewContentElementWizardButton.prototype,"url",void 0),__decorate([property({type:String})],NewContentElementWizardButton.prototype,"title",void 0),NewContentElementWizardButton=NewContentElementWizardButton_1=__decorate([customElement("typo3-backend-new-content-element-wizard-button")],NewContentElementWizardButton);export{NewContentElementWizardButton};