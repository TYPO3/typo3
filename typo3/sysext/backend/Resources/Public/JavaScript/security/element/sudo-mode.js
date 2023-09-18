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
var __decorate=function(e,o,s,t){var r,l=arguments.length,a=l<3?o:null===t?t=Object.getOwnPropertyDescriptor(o,s):t;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(e,o,s,t);else for(var d=e.length-1;d>=0;d--)(r=e[d])&&(a=(l<3?r(a):l>3?r(o,s,a):r(o,s))||a);return l>3&&a&&Object.defineProperty(o,s,a),a};import{customElement,property,query,state}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{lll}from"@typo3/core/lit-helper.js";import Viewport from"@typo3/backend/viewport.js";let SudoMode=class extends LitElement{constructor(){super(...arguments),this.useInstallToolPassword=!1,this.errorMessage=null}createRenderRoot(){return this}render(){return html`
      <div id="sudo-mode-verification" class="modal modal-severity-notice modal-size-small" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">
                ${lll(this.useInstallToolPassword?"verifyWithInstallToolPassword":"verifyWithUserPassword")}
              </h4>
            </div>
            <div class="modal-body">
              <div>
                ${this.errorMessage?html`
                  <div class="alert alert-danger" id="invalid-password">${lll(this.errorMessage)||this.errorMessage}</div>
                `:nothing}
                <form method="post" class="form" id="verify-sudo-mode" spellcheck="false" @submit=${e=>this.verifyPassword(e)}>
                  <div class="form-group">
                    <label class="form-label" for="password">${lll("password")}</label>
                    <input required="required" class="form-control" id="password" type="password" name="password"
                            autocomplete=${this.useInstallToolPassword?"section-install current-password":"current-password"}>
                  </div>
                </form>
                <div class="text-end">
                  <a href="#" @click=${e=>this.toggleUseInstallToolPassword(e)}>
                    ${lll(this.useInstallToolPassword?"userPasswordMode":"installToolPasswordMode")}
                  </a>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" form="verify-sudo-mode" class="btn btn-primary" role="button">
                ${lll("verify")}
              </button>
            </div>
          </div>
        </div>
      </div>
    `}firstUpdated(e){super.firstUpdated(e),this.passwordElement.focus()}verifyPassword(e){e.preventDefault(),this.errorMessage=null,new AjaxRequest(this.verifyActionUri).post({password:this.passwordElement.value,useInstallToolPassword:this.useInstallToolPassword?1:0}).then((async e=>{const o=await e.resolve("application/json");o.redirect&&Viewport.ContentContainer.setUrl(o.redirect.uri)})).catch((async e=>{const o=await e.resolve("application/json");this.errorMessage=o.message}))}toggleUseInstallToolPassword(e){e.preventDefault(),this.useInstallToolPassword=!this.useInstallToolPassword}};__decorate([property({type:String})],SudoMode.prototype,"verifyActionUri",void 0),__decorate([state()],SudoMode.prototype,"useInstallToolPassword",void 0),__decorate([state()],SudoMode.prototype,"errorMessage",void 0),__decorate([query("#password")],SudoMode.prototype,"passwordElement",void 0),SudoMode=__decorate([customElement("typo3-backend-security-sudo-mode")],SudoMode);export{SudoMode};