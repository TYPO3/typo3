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
var __decorate=function(t,e,i,n){var o,s=arguments.length,a=s<3?e:null===n?n=Object.getOwnPropertyDescriptor(e,i):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(t,e,i,n);else for(var r=t.length-1;r>=0;r--)(o=t[r])&&(a=(s<3?o(a):s>3?o(e,i,a):o(e,i))||a);return s>3&&a&&Object.defineProperty(e,i,a),a};import{html,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import{until}from"lit/directives/until.js";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";import{copyToClipboard}from"@typo3/backend/copy-to-clipboard.js";import Notification from"@typo3/backend/notification.js";import{lll}from"@typo3/core/lit-helper.js";import{markdown}from"@typo3/core/directive/markdown.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";let EditableSettingElement=class extends LitElement{constructor(){super(...arguments),this.debug=!1,this.hasChange=!1,this.typeElement=null}createRenderRoot(){return this}render(){const{value:t,systemDefault:e,definition:i}=this.setting;return html`
      <div
        class=${`settings-item settings-item-${i.type} ${this.hasChange?"has-change":""}`}
        tabindex="0"
        data-status=${JSON.stringify(t)===JSON.stringify(e)?"none":"modified"}
      >
        <!-- data-status=modified|error|none-->
        <div class="settings-item-indicator"></div>
        <div class="settings-item-title">
          <label for=${`setting-${i.key}`} class="settings-item-label">${i.label}</label>
          <div class="settings-item-description">${markdown(i.description??"","minimal")}</div>
          ${this.debug?html`<div class="settings-item-key">${i.key}</div>`:nothing}
        </div>
        <div class="settings-item-control">
          ${until(this.renderField(),html`<typo3-backend-spinner></typo3-backend-spinner>`)}
        </div>
        <div class="settings-item-message"></div>
        <div class="settings-item-actions">
          ${this.renderActions()}
        </div>
      </div>
    `}async renderField(){const{definition:t,value:e,typeImplementation:i}=this.setting;let n=this.typeElement;if(!n){const t=await import(i);if(!("componentName"in t))throw new Error(`module ${i} is missing the "componentName" export`);n=document.createElement(t.componentName),this.typeElement=n,n.addEventListener("typo3:setting:changed",(t=>{this.hasChange=JSON.stringify(this.setting.value)!==JSON.stringify(t.detail.value)}))}const o=Object.entries(t.enum||{}),s={key:t.key,formid:`setting-${t.key}`,name:`settings[${t.key}]`,value:Array.isArray(e)?JSON.stringify(e):String(e),debug:this.debug,readonly:t.readonly,enum:o.length>0&&JSON.stringify(Object.fromEntries(o)),default:Array.isArray(t.default)?JSON.stringify(t.default):String(t.default)};for(const[t,e]of Object.entries(s))"boolean"!=typeof e?n.getAttribute(t)!==e&&n.setAttribute(t,e):(e&&!n.hasAttribute(t)&&n.setAttribute(t,""),!e&&n.hasAttribute(t)&&n.removeAttribute(t));return n}renderActions(){const{definition:t}=this.setting;return html`
      <div class="dropdown">
        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
          <span class="visually-hidden">More actions</span>
        </button>
        <ul class="dropdown-menu">
          <li>
            <button class="dropdown-item dropdown-item-spaced"
              type="button"
              ?disabled=${t.readonly}
              @click="${()=>this.setToDefaultValue()}">
              <typo3-backend-icon identifier="actions-undo" size="small"></typo3-backend-icon> ${lll("edit.resetSetting")}
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <typo3-copy-to-clipboard
              text=${t.key}
              class="dropdown-item dropdown-item-spaced"
            >
              <typo3-backend-icon identifier="actions-clipboard" size="small"></typo3-backend-icon> ${lll("edit.copySettingsIdentifier")}
            </typo3-copy-to-clipboard>
          </li>
          ${this.dumpuri?html`
            <li>
              <button class="dropdown-item dropdown-item-spaced"
                type="button"
                @click="${()=>this.copyAsYaml()}">
                <typo3-backend-icon identifier="actions-clipboard-paste" size="small"></typo3-backend-icon> ${lll("edit.copyAsYaml")}

              </a>
            </li>
          `:nothing}
        </ul>
      </div>
    `}setToDefaultValue(){this.typeElement&&(this.typeElement.value=this.setting.systemDefault)}async copyAsYaml(){const t=new FormData(this.typeElement.form),e=`settings[${this.setting.definition.key}]`,i=t.get(e),n=new FormData;n.append("specificSetting",this.setting.definition.key),n.append(e,i);const o=await new AjaxRequest(this.dumpuri).post(n),s=await o.resolve();"string"==typeof s.yaml?copyToClipboard(s.yaml):(console.warn("Value can not be copied to clipboard.",typeof s.yaml),Notification.error(lll("copyToClipboard.error")))}};__decorate([property({type:Object})],EditableSettingElement.prototype,"setting",void 0),__decorate([property({type:String})],EditableSettingElement.prototype,"dumpuri",void 0),__decorate([property({type:Boolean})],EditableSettingElement.prototype,"debug",void 0),__decorate([state()],EditableSettingElement.prototype,"hasChange",void 0),EditableSettingElement=__decorate([customElement("typo3-backend-editable-setting")],EditableSettingElement);export{EditableSettingElement};