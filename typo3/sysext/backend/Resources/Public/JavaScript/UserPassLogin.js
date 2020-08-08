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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Login"],(function(e,s,t,o){"use strict";t=__importDefault(t);class i{constructor(){this.resetPassword=()=>{const e=t.default(this.options.passwordField);e.val()&&(t.default(o.options.useridentField).val(e.val()),e.val(""))},this.showCapsLockWarning=e=>{t.default(e.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!i.isCapslockEnabled(e))},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field"},o.options.submitHandler=this.resetPassword;const e=t.default(this.options.usernameField),s=t.default(this.options.passwordField);e.on("keypress",this.showCapsLockWarning),s.on("keypress",this.showCapsLockWarning);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&e.val(parent.opener.TYPO3.configuration.username)}catch(e){}""===e.val()?e.focus():s.focus()}static isCapslockEnabled(e){const s=e||window.event;if(!s)return!1;let t=-1;s.which?t=s.which:s.keyCode&&(t=s.keyCode);let o=!1;return s.shiftKey?o=s.shiftKey:s.modifiers&&(o=!!(4&s.modifiers)),t>=65&&t<=90&&!o||t>=97&&t<=122&&o}}return new i}));