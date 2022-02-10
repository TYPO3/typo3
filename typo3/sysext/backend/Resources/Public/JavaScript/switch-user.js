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
var Modes,__decorate=function(t,e,r,o){var i,s=arguments.length,c=s<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(t,e,r,o);else for(var n=t.length-1;n>=0;n--)(i=t[n])&&(c=(s<3?i(c):s>3?i(e,r,c):i(e,r))||c);return s>3&&c&&Object.defineProperty(e,r,c),c};import{html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";!function(t){t.switch="switch",t.exit="exit"}(Modes||(Modes={}));let SwitchUser=class extends LitElement{constructor(){super(),this.mode=Modes.switch,this.addEventListener("click",t=>{t.preventDefault(),this.mode===Modes.switch?this.handleSwitchUser():this.mode===Modes.exit&&this.handleExitSwitchUser()})}render(){return html`<slot></slot>`}handleSwitchUser(){this.targetUser?new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:this.targetUser}).then(async t=>{const e=await t.resolve();!0===e.success&&e.url?top.window.location.href=e.url:Notification.error("Switching to user went wrong.")}):Notification.error("Switching to user went wrong.")}handleExitSwitchUser(){new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user_exit).post({}).then(async t=>{const e=await t.resolve();!0===e.success&&e.url?top.window.location.href=e.url:Notification.error("Exiting current user went wrong.")})}};__decorate([property({type:String})],SwitchUser.prototype,"targetUser",void 0),__decorate([property({type:Modes})],SwitchUser.prototype,"mode",void 0),SwitchUser=__decorate([customElement("typo3-backend-switch-user")],SwitchUser);