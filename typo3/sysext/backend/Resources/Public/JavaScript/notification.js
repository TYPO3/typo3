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
var __decorate=function(t,e,i,o){var s,a=arguments.length,n=a<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,i,o);else for(var r=t.length-1;r>=0;r--)(s=t[r])&&(n=(a<3?s(n):a>3?s(e,i,n):s(e,i))||n);return a>3&&n&&Object.defineProperty(e,i,n),n};import{LitElement,html}from"lit";import{customElement,property,state}from"lit/decorators.js";import{classMap}from"lit/directives/class-map.js";import{ifDefined}from"lit/directives/if-defined.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Severity from"@typo3/backend/severity.js";class Notification{static notice(t,e,i,o){Notification.showMessage(t,e,SeverityEnum.notice,i,o)}static info(t,e,i,o){Notification.showMessage(t,e,SeverityEnum.info,i,o)}static success(t,e,i,o){Notification.showMessage(t,e,SeverityEnum.ok,i,o)}static warning(t,e,i,o){Notification.showMessage(t,e,SeverityEnum.warning,i,o)}static error(t,e,i=0,o){Notification.showMessage(t,e,SeverityEnum.error,i,o)}static showMessage(t,e,i=SeverityEnum.info,o=this.duration,s=[]){o=void 0===o?this.duration:o,null!==this.messageContainer&&null!==document.getElementById("alert-container")||(this.messageContainer=document.createElement("div"),this.messageContainer.setAttribute("id","alert-container"),document.body.appendChild(this.messageContainer));const a=document.createElement("typo3-notification-message");a.setAttribute("notificationId","notification-"+Math.random().toString(36).substr(2,5)),a.setAttribute("title",t),e&&a.setAttribute("message",e),a.setAttribute("severity",i.toString()),a.setAttribute("duration",o.toString()),a.actions=s,this.messageContainer.appendChild(a)}}Notification.duration=5,Notification.messageContainer=null;let notificationObject,NotificationMessage=class extends LitElement{constructor(){super(...arguments),this.severity=SeverityEnum.info,this.duration=0,this.actions=[],this.visible=!1,this.executingAction=-1}createRenderRoot(){return this}async firstUpdated(){await new Promise(t=>window.setTimeout(t,200)),this.visible=!0,await this.requestUpdate(),this.duration>0&&(await new Promise(t=>window.setTimeout(t,1e3*this.duration)),this.close())}async close(){this.visible=!1;const t=()=>{this.parentNode&&this.parentNode.removeChild(this)};"animate"in this?(this.style.overflow="hidden",this.style.display="block",this.animate([{height:this.getBoundingClientRect().height+"px"},{height:0}],{duration:400,easing:"cubic-bezier(.02, .01, .47, 1)"}).onfinish=t):t()}render(){const t=Severity.getCssClass(this.severity);let e="";switch(this.severity){case SeverityEnum.notice:e="lightbulb-o";break;case SeverityEnum.ok:e="check";break;case SeverityEnum.warning:e="exclamation";break;case SeverityEnum.error:e="times";break;case SeverityEnum.info:default:e="info"}return html`
      <div
        id="${ifDefined(this.notificationId||void 0)}"
        class="${"alert alert-"+t+" alert-dismissible fade"+(this.visible?" in":"")}"
        role="alert">
        <button type="button" class="close" @click="${async t=>this.close()}">
          <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
          <span class="sr-only">Close</span>
        </button>
        <div class="media">
          <div class="media-left">
            <span class="fa-stack fa-lg">
              <i class="fa fa-circle fa-stack-2x"></i>
              <i class="${"fa fa-"+e+" fa-stack-1x"}"></i>
            </span>
          </div>
          <div class="media-body">
            <h4 class="alert-title">${this.title}</h4>
            <p class="alert-message text-pre-wrap">${this.message?this.message:""}</p>
          </div>
        </div>
        ${0===this.actions.length?"":html`
          <div class="alert-actions">
            ${this.actions.map((t,e)=>html`
              <a href="#"
                 title="${t.label}"
                 @click="${async i=>{i.preventDefault(),this.executingAction=e,await this.updateComplete,"action"in t&&await t.action.execute(i.currentTarget),this.close()}}"
                 class="${classMap({executing:this.executingAction===e,disabled:this.executingAction>=0&&this.executingAction!==e})}"
                >${t.label}</a>
            `)}
          </div>
        `}
      </div>
    `}};__decorate([property()],NotificationMessage.prototype,"notificationId",void 0),__decorate([property()],NotificationMessage.prototype,"title",void 0),__decorate([property()],NotificationMessage.prototype,"message",void 0),__decorate([property({type:Number})],NotificationMessage.prototype,"severity",void 0),__decorate([property()],NotificationMessage.prototype,"duration",void 0),__decorate([property({type:Array,attribute:!1})],NotificationMessage.prototype,"actions",void 0),__decorate([state()],NotificationMessage.prototype,"visible",void 0),__decorate([state()],NotificationMessage.prototype,"executingAction",void 0),NotificationMessage=__decorate([customElement("typo3-notification-message")],NotificationMessage);try{parent&&parent.window.TYPO3&&parent.window.TYPO3.Notification&&(notificationObject=parent.window.TYPO3.Notification),top&&top.TYPO3.Notification&&(notificationObject=top.TYPO3.Notification)}catch{}notificationObject||(notificationObject=Notification,"undefined"!=typeof TYPO3&&(TYPO3.Notification=notificationObject));export default notificationObject;