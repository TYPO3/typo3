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
import{LitElement as d,css as p,html as m}from"lit";import{property as w,customElement as x}from"lit/decorators.js";import f from"@typo3/core/ajax/ajax-request.js";import l from"@typo3/backend/notification.js";var u=function(s,t,e,n){var c=arguments.length,r=c<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,e):n,h;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")r=Reflect.decorate(s,t,e,n);else for(var a=s.length-1;a>=0;a--)(h=s[a])&&(r=(c<3?h(r):c>3?h(t,e,r):h(t,e))||r);return c>3&&r&&Object.defineProperty(t,e,r),r},i;(function(s){s.switch="switch",s.exit="exit"})(i||(i={}));let o=class extends d{static{this.styles=[p`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.mode=i.switch,this.addEventListener("click",t=>{t.preventDefault(),this.mode===i.switch?this.handleSwitchUser():this.mode===i.exit&&this.handleExitSwitchUser()}),this.addEventListener("keydown",t=>{(t.key==="Enter"||t.key===" ")&&(t.preventDefault(),this.mode===i.switch?this.handleSwitchUser():this.mode===i.exit&&this.handleExitSwitchUser())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return m`<slot></slot>`}handleSwitchUser(){if(!this.targetUser){l.error("Switching to user went wrong.");return}new f(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:this.targetUser}).then(async t=>{const e=await t.resolve();e.success===!0&&e.url?top.window.location.href=e.url:l.error("Switching to user went wrong.")})}handleExitSwitchUser(){new f(TYPO3.settings.ajaxUrls.switch_user_exit).post({}).then(async t=>{const e=await t.resolve();e.success===!0&&e.url?top.window.location.href=e.url:l.error("Exiting current user went wrong.")})}};u([w({type:String})],o.prototype,"targetUser",void 0),u([w({type:i})],o.prototype,"mode",void 0),o=u([x("typo3-backend-switch-user")],o);export{o as SwitchUser};
