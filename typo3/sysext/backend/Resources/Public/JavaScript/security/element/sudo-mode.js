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
var __decorate=function(e,o,t,s){var r,a=arguments.length,i=a<3?o:null===s?s=Object.getOwnPropertyDescriptor(o,t):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,o,t,s);else for(var d=e.length-1;d>=0;d--)(r=e[d])&&(i=(a<3?r(i):a>3?r(o,t,i):r(o,t))||i);return a>3&&i&&Object.defineProperty(o,t,i),i};import{customElement,property,query,state}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{AjaxResponse}from"@typo3/core/ajax/ajax-response.js";import Viewport from"@typo3/backend/viewport.js";import{topLevelModuleImport}from"@typo3/backend/utility/top-level-module-import.js";import Modal,{Sizes}from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";class SudoModeProperties extends LitElement{}__decorate([property({type:String})],SudoModeProperties.prototype,"verifyActionUri",void 0),__decorate([property({type:String})],SudoModeProperties.prototype,"cancelUri",void 0),__decorate([property({type:Boolean})],SudoModeProperties.prototype,"isAjax",void 0),__decorate([property({type:Boolean,attribute:"has-fatal-error"})],SudoModeProperties.prototype,"hasFatalError",void 0),__decorate([property({type:Boolean,attribute:"allow-install-tool-password"})],SudoModeProperties.prototype,"allowInstallToolPassword",void 0),__decorate([property({type:Object})],SudoModeProperties.prototype,"labels",void 0);export const initiateSudoModeModal=async e=>{window.location!==window.parent.location&&topLevelModuleImport("@typo3/backend/security/element/sudo-mode.js");const o=top.document.createElement("typo3-backend-security-sudo-mode");return Object.assign(o,e),o.windowRef=window,top.document.body.append(o),new Promise(((e,t)=>{o.addEventListener("typo3:sudo-mode:verified",(()=>e())),o.addEventListener("typo3:sudo-mode:finished",(()=>t()))}))};let SudoMode=class extends SudoModeProperties{render(){return nothing}async firstUpdated(){if(window.location!==window.parent.location)try{await initiateSudoModeModal(this.getPropertyValues())}catch{history.go(-1)}else Modal.advanced({title:this.hasFatalError?this.labels.verificationFailed:this.labels.verifyWithUserPassword,severity:this.hasFatalError?SeverityEnum.error:SeverityEnum.notice,size:Sizes.small,additionalCssClasses:["modal-sudo-mode-verification"],buttons:[this.hasFatalError?{text:this.labels.cancel,btnClass:"btn-default",trigger:()=>{top.location.href=this.cancelUri}}:{text:this.labels.verify,name:"verify",form:"verify-sudo-mode",btnClass:"btn-primary"}],content:html`
        <typo3-backend-security-sudo-mode-form
          .labels=${this.labels}
          .verifyActionUri=${this.verifyActionUri}
          .cancelUri=${this.cancelUri}
          .isAjax=${this.isAjax}
          .hasFatalError=${this.hasFatalError}
          .allowInstallToolPassword=${this.allowInstallToolPassword}
          .windowRef=${this.windowRef}
          @typo3:sudo-mode:verified=${()=>this.dispatchEvent(new Event("typo3:sudo-mode:verified"))}
        ></typo3-backend-security-sudo-mode-form>
      `}).addEventListener("typo3-modal-hidden",(()=>{this.dispatchEvent(new Event("typo3:sudo-mode:finished")),this.remove()}))}getPropertyValues(){const e={},o=this.constructor;for(const t of o.elementProperties.keys())e[t]=this[t];return e}};SudoMode=__decorate([customElement("typo3-backend-security-sudo-mode")],SudoMode);export{SudoMode};let SudoModeForm=class extends SudoModeProperties{constructor(){super(...arguments),this.useInstallToolPassword=!1,this.errorMessage=null}createRenderRoot(){return this}render(){return this.hasFatalError?html`
        <div>
          <div class="alert alert-danger">${this.labels.verificationExpired}</div>
        </div>
      `:html`
      <div>
        ${this.errorMessage?html`
          <div class="alert alert-danger" id="invalid-password">${this.labels[this.errorMessage]||this.errorMessage}</div>
        `:nothing}
        <p>${this.useInstallToolPassword?this.labels.sudoModeInstallToolPasswordExplanation:this.labels.sudoModeUserPasswordExplanation}</p>
        <form method="post" class="form" id="verify-sudo-mode" spellcheck="false" @submit=${e=>this.verifyPassword(e)}>
          ${this.useInstallToolPassword?nothing:html`
            <input hidden aria-hidden="true" type="text" autocomplete="username" value=${TYPO3.configuration.username}>
          `}
          <div class="form-group">
            <label class="form-label" for="password">${this.labels.password}</label>
            <input required="required" class="form-control" id="password" type="password" name="password" autofocus
                   autocomplete=${this.useInstallToolPassword?"section-install current-password":"current-password"}>
          </div>
        </form>
        ${this.allowInstallToolPassword?html`
          <div class="text-end">
            <a href="#" @click=${e=>this.toggleUseInstallToolPassword(e)}>
              ${this.useInstallToolPassword?this.labels.userPasswordMode:this.labels.installToolPasswordMode}
            </a>
          </div>
        `:nothing}
      </div>
    `}updated(e){e.has("useInstallToolPassword")&&(this.closest("typo3-backend-modal").modalTitle=this.getModalTitle())}getModalTitle(){return this.hasFatalError?this.labels.verificationFailed:this.useInstallToolPassword?this.labels.verifyWithInstallToolPassword:this.labels.verifyWithUserPassword}async verifyPassword(e){e.preventDefault(),this.errorMessage=null;try{const e=await new AjaxRequest(this.verifyActionUri).post({password:this.passwordElement.value,useInstallToolPassword:this.useInstallToolPassword?1:0}),o=await e.resolve("application/json");if(this.dispatchEvent(new Event("typo3:sudo-mode:verified")),this.closest("typo3-backend-modal").hideModal(),!this.isAjax&&o.redirect){const{uri:e}=o.redirect,t=this.windowRef??window;"list_frame"===t.name?Viewport.ContentContainer.setUrl(e):t.location.assign(e)}}catch(e){if(!(e instanceof AjaxResponse))throw e;{const o=await e.resolve("application/json");this.errorMessage=o.message}}}toggleUseInstallToolPassword(e){e.preventDefault(),this.useInstallToolPassword=!this.useInstallToolPassword}};__decorate([state()],SudoModeForm.prototype,"useInstallToolPassword",void 0),__decorate([state()],SudoModeForm.prototype,"errorMessage",void 0),__decorate([query("#password")],SudoModeForm.prototype,"passwordElement",void 0),SudoModeForm=__decorate([customElement("typo3-backend-security-sudo-mode-form")],SudoModeForm);export{SudoModeForm};