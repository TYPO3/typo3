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
var __decorate=function(t,i,e,o){var n,a=arguments.length,s=a<3?i:null===o?o=Object.getOwnPropertyDescriptor(i,e):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,i,e,o);else for(var c=t.length-1;c>=0;c--)(n=t[c])&&(s=(a<3?n(s):a>3?n(i,e,s):n(i,e))||s);return a>3&&s&&Object.defineProperty(i,e,s),s};import{LitElement,html}from"lit";import{customElement,property,state}from"lit/decorators.js";import{classMap}from"lit/directives/class-map.js";import{ifDefined}from"lit/directives/if-defined.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Severity from"@typo3/backend/severity.js";import"@typo3/backend/element/icon-element.js";import{lll}from"@typo3/core/lit-helper.js";class Notification{static notice(t,i,e,o){Notification.showMessage(t,i,SeverityEnum.notice,e,o)}static info(t,i,e,o){Notification.showMessage(t,i,SeverityEnum.info,e,o)}static success(t,i,e,o){Notification.showMessage(t,i,SeverityEnum.ok,e,o)}static warning(t,i,e,o){Notification.showMessage(t,i,SeverityEnum.warning,e,o)}static error(t,i,e=0,o){Notification.showMessage(t,i,SeverityEnum.error,e,o)}static showMessage(t,i,e=SeverityEnum.info,o,n=[]){void 0===o&&(o=e===SeverityEnum.error?0:this.duration),null!==this.messageContainer&&null!==document.getElementById("alert-container")||(this.messageContainer=document.createElement("div"),this.messageContainer.setAttribute("id","alert-container"),this.notificationList=document.createElement("div"),this.notificationList.setAttribute("class","alert-list"),this.notificationList.setAttribute("tabindex","0"),this.messageContainer.appendChild(this.notificationList),this.clearAllButton=document.createElement("typo3-notification-clear-all"),this.containerItemVisibility(),this.messageContainer.prepend(this.clearAllButton),document.body.appendChild(this.messageContainer),document.addEventListener("typo3-notification-open",(()=>{this.totalNotifications++,this.containerItemVisibility()})),document.addEventListener("typo3-notification-clear",(()=>{this.totalNotifications>0&&this.totalNotifications--,this.containerItemVisibility()})));const a=document.createElement("typo3-notification-message");a.setAttribute("notification-id","notification-"+Math.random().toString(36).substring(2,6)),a.setAttribute("notification-title",t),i&&a.setAttribute("notification-message",i),a.setAttribute("notification-severity",e.toString()),a.setAttribute("notification-duration",o.toString()),a.actions=n,setTimeout((()=>{this.notificationList.querySelector("typo3-notification-message:last-child").scrollIntoView()}),Number(o)),this.notificationList.appendChild(a)}static containerItemVisibility(){this.clearAllButton.hidden=this.totalNotifications<this.showClearAllButtonCount,this.messageContainer.hidden=0===this.totalNotifications}}Notification.duration=5,Notification.showClearAllButtonCount=2,Notification.totalNotifications=0,Notification.messageContainer=null,Notification.notificationList=null,Notification.clearAllButton=null;let ClearNotificationMessages=class extends LitElement{async clearAll(){this.dispatchEvent(new CustomEvent("typo3-notification-clear-all",{bubbles:!0,composed:!0})),this.hidden=!0}createRenderRoot(){return this}render(){return html`<div><button @click=${()=>this.clearAll()} class="btn btn-default">
      <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon> ${lll("button.clearAll")||"Clear all"}
    </button></div>`}};__decorate([property({type:String,attribute:"notification-container"})],ClearNotificationMessages.prototype,"notificationId",void 0),ClearNotificationMessages=__decorate([customElement("typo3-notification-clear-all")],ClearNotificationMessages);export{ClearNotificationMessages};let notificationObject,NotificationMessage=class extends LitElement{constructor(){super(...arguments),this.notificationSeverity=SeverityEnum.info,this.notificationDuration=0,this.actions=[],this.executingAction=-1}async firstUpdated(){document.addEventListener("typo3-notification-clear-all",(async()=>{this.clear()}));const t=new CustomEvent("typo3-notification-open",{bubbles:!0,composed:!0});this.dispatchEvent(t),await new Promise((t=>window.setTimeout(t,200))),await this.requestUpdate(),this.notificationDuration>0&&(await new Promise((t=>window.setTimeout(t,1e3*this.notificationDuration))),this.clear())}async clear(){this.dispatchEvent(new CustomEvent("typo3-notification-clear",{bubbles:!0,composed:!0})),this.addEventListener("typo3-notification-clear-finish",(()=>{this.parentNode&&this.parentNode.removeChild(this)}));const t=()=>{this.dispatchEvent(new CustomEvent("typo3-notification-clear-finish"))};!window.matchMedia("(prefers-reduced-motion: reduce)").matches&&"animate"in this?(this.style.overflow="hidden",this.style.display="block",this.animate([{height:this.getBoundingClientRect().height+"px"},{height:0,opacity:0,marginTop:0}],{duration:400,easing:"cubic-bezier(.02, .01, .47, 1)"}).onfinish=t):t()}createRenderRoot(){return this}render(){const t=Severity.getCssClass(this.notificationSeverity);let i="";switch(this.notificationSeverity){case SeverityEnum.notice:i="actions-lightbulb";break;case SeverityEnum.ok:i="actions-check";break;case SeverityEnum.warning:i="actions-exclamation";break;case SeverityEnum.error:i="actions-close";break;case SeverityEnum.info:default:i="actions-info"}const e=(Math.random()+1).toString(36).substring(2);return html`
      <div
        id="${ifDefined(this.notificationId||void 0)}"
        class="alert alert-${t} alert-dismissible"
        role="alertdialog"
        aria-labelledby="alert-title-${e}"
        aria-describedby="alert-message-${e}"
      >
        <button type="button" class="close" @click="${async()=>this.clear()}">
          <span aria-hidden="true"><typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon></span>
          <span class="visually-hidden">Close</span>
        </button>
        <div class="alert-inner">
          <div class="alert-icon">
            <span class="icon-emphasized">
              <typo3-backend-icon identifier="${i}" size="small"></typo3-backend-icon>
            </span>
          </div>
          <div class="alert-content">
            <div class="alert-title" id="alert-title-${e}">${this.notificationTitle}</div>
            <p class="alert-message" id="alert-message-${e}">${this.notificationMessage?this.notificationMessage:""}</p>
          </div>
        </div>
        ${0===this.actions.length?"":html`
          <div class="alert-actions">
            ${this.actions.map(((t,i)=>html`
              <a href="#"
                 title="${t.label}"
                 @click="${async e=>{e.preventDefault(),this.executingAction=i,await this.updateComplete,"action"in t&&await t.action.execute(e.currentTarget),this.clear()}}"
                 class="${classMap({executing:this.executingAction===i,disabled:this.executingAction>=0&&this.executingAction!==i})}"
                >${t.label}</a>
            `))}
          </div>
        `}
      </div>
    `}};__decorate([property({type:String,attribute:"notification-id"})],NotificationMessage.prototype,"notificationId",void 0),__decorate([property({type:String,attribute:"notification-title"})],NotificationMessage.prototype,"notificationTitle",void 0),__decorate([property({type:String,attribute:"notification-message"})],NotificationMessage.prototype,"notificationMessage",void 0),__decorate([property({type:Number,attribute:"notification-severity"})],NotificationMessage.prototype,"notificationSeverity",void 0),__decorate([property({type:Number,attribute:"notification-duration"})],NotificationMessage.prototype,"notificationDuration",void 0),__decorate([property({type:Array,attribute:!1})],NotificationMessage.prototype,"actions",void 0),__decorate([state()],NotificationMessage.prototype,"executingAction",void 0),NotificationMessage=__decorate([customElement("typo3-notification-message")],NotificationMessage);export{NotificationMessage};try{parent&&parent.window.TYPO3&&parent.window.TYPO3.Notification&&(notificationObject=parent.window.TYPO3.Notification),top&&top.TYPO3.Notification&&(notificationObject=top.TYPO3.Notification)}catch{}notificationObject||(notificationObject=Notification,"undefined"!=typeof TYPO3&&(TYPO3.Notification=notificationObject));export default notificationObject;