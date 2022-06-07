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
var __decorate=this&&this.__decorate||function(e,t,r,o){var s,i=arguments.length,n=i<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,r,o);else for(var c=e.length-1;c>=0;c--)(s=e[c])&&(n=(i<3?s(n):i>3?s(t,r,n):s(t,r))||n);return i>3&&n&&Object.defineProperty(t,r,n),n},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Backend/Notification"],(function(e,t,r,o,s,i){"use strict";var n;Object.defineProperty(t,"__esModule",{value:!0}),s=__importDefault(s),function(e){e.switch="switch",e.exit="exit"}(n||(n={}));let c=class extends r.LitElement{constructor(){super(),this.mode=n.switch,this.addEventListener("click",e=>{e.preventDefault(),this.mode===n.switch?this.handleSwitchUser():this.mode===n.exit&&this.handleExitSwitchUser()})}render(){return r.html`<slot></slot>`}handleSwitchUser(){this.targetUser?new s.default(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:this.targetUser}).then(async e=>{const t=await e.resolve();!0===t.success&&t.url?top.window.location.href=t.url:i.error("Switching to user went wrong.")}):i.error("Switching to user went wrong.")}handleExitSwitchUser(){new s.default(TYPO3.settings.ajaxUrls.switch_user_exit).post({}).then(async e=>{const t=await e.resolve();!0===t.success&&t.url?top.window.location.href=t.url:i.error("Exiting current user went wrong.")})}};__decorate([(0,o.property)({type:String})],c.prototype,"targetUser",void 0),__decorate([(0,o.property)({type:n})],c.prototype,"mode",void 0),c=__decorate([(0,o.customElement)("typo3-backend-switch-user")],c)}));