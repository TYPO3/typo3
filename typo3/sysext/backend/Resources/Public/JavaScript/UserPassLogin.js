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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Login"],(function(e,t,s,i){"use strict";s=__importDefault(s);class o{constructor(){this.resetPassword=()=>{const e=(0,s.default)(this.options.passwordField);e.val()&&((0,s.default)(i.options.useridentField).val(e.val()),e.val(""))},this.showCapsLockWarning=e=>{(0,s.default)(e.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!o.isCapslockEnabled(e))},this.toggleCopyright=e=>{" "===e.key&&e.target.click()},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field",copyrightLink:"t3js-login-copyright-link"},i.options.submitHandler=this.resetPassword;const e=(0,s.default)(this.options.usernameField),t=(0,s.default)(this.options.passwordField),n=document.getElementsByClassName(this.options.copyrightLink)[0];e.on("keypress",this.showCapsLockWarning),t.on("keypress",this.showCapsLockWarning),n.addEventListener("keydown",this.toggleCopyright);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&e.val(parent.opener.TYPO3.configuration.username)}catch(e){}""===e.val()?e.focus():t.focus()}static isCapslockEnabled(e){const t=e||window.event;if(!t)return!1;let s=-1;t.which?s=t.which:t.keyCode&&(s=t.keyCode);let i=!1;return t.shiftKey?i=t.shiftKey:t.modifiers&&(i=!!(4&t.modifiers)),s>=65&&s<=90&&!i||s>=97&&s<=122&&i}}return new o}));