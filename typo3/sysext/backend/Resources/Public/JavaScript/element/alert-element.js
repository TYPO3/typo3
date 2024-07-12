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
var AlertElement_1,__decorate=function(e,t,i,r){var s,o=arguments.length,l=o<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,i,r);else for(var n=e.length-1;n>=0;n--)(s=e[n])&&(l=(o<3?s(l):o>3?s(t,i,l):s(t,i))||l);return o>3&&l&&Object.defineProperty(t,i,l),l};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{classMap}from"lit/directives/class-map.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Severity from"@typo3/backend/severity.js";import"@typo3/backend/element/icon-element.js";import{lll}from"@typo3/core/lit-helper.js";let AlertElement=AlertElement_1=class extends LitElement{constructor(){super(...arguments),this.severity=SeverityEnum.info,this.dismissible=!1,this.visible=!0,this.heading=null,this.message=null,this.showIcon=!1,this.randomSuffix=Math.random().toString(36).substring(7)}static getIconIdentifier(e){return{[SeverityEnum.notice]:"actions-lightbulb",[SeverityEnum.ok]:"actions-check",[SeverityEnum.warning]:"actions-exclamation",[SeverityEnum.error]:"actions-close",[SeverityEnum.info]:"actions-info"}[e]||"actions-info"}createRenderRoot(){return this}render(){return html`
      <div
        id="alert-${this.randomSuffix}"
        class=${classMap(this.getClasses())}
        role="alert"
        aria-labelledby="alert-title-${this.randomSuffix}"
        aria-describedby="alert-message-${this.randomSuffix}"
        @closed.bs.alert="${this.remove}"
      >
        <div class="alert-inner">
          ${this.showIcon?html`
            <div class="alert-icon">
              <span class="icon-emphasized">
                <typo3-backend-icon identifier="${AlertElement_1.getIconIdentifier(this.severity)}" size="small"></typo3-backend-icon>
              </span>
            </div>
          `:nothing}
          <div class="alert-content">
            ${this.heading?html`<h4 class="alert-title" id="alert-title-${this.randomSuffix}">${this.heading}</h4>`:nothing}
            <p class="alert-body" id="alert-message-${this.randomSuffix}">${this.message}</p>
          </div>
        </div>
        ${this.dismissible?this.renderDismissButton():nothing}
      </div>
    `}getClasses(){return{alert:!0,["alert-"+Severity.getCssClass(this.severity)]:!0,"alert-dismissible":this.dismissible,fade:!0,show:this.visible,hidden:!this.visible}}renderDismissButton(){return html`
      <button type="button" class="close" data-bs-dismiss="alert" aria-label="${lll("button.close")||"Close"}">
        <span aria-hidden="true"><typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon></span>
        <span class="visually-hidden">${lll("button.close")||"Close"}</span>
      </button>
    `}};__decorate([property({type:Number})],AlertElement.prototype,"severity",void 0),__decorate([property({type:Boolean})],AlertElement.prototype,"dismissible",void 0),__decorate([property({type:Boolean})],AlertElement.prototype,"visible",void 0),__decorate([property({type:String})],AlertElement.prototype,"heading",void 0),__decorate([property({type:String})],AlertElement.prototype,"message",void 0),__decorate([property({type:Boolean,attribute:"show-icon"})],AlertElement.prototype,"showIcon",void 0),AlertElement=AlertElement_1=__decorate([customElement("typo3-backend-alert")],AlertElement);export{AlertElement};