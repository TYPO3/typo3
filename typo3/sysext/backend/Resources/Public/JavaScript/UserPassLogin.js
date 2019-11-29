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
define(["require","exports","jquery","./Login"],(function(e,s,n,o){"use strict";class i{constructor(){this.resetPassword=()=>{const e=n(this.options.passwordField);e.val()&&(n(o.options.useridentField).val(e.val()),e.val(""))},this.showCapsLockWarning=e=>{n(e.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!i.isCapslockEnabled(e))},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field"},o.options.submitHandler=this.resetPassword;const e=n(this.options.usernameField),s=n(this.options.passwordField);e.on("keypress",this.showCapsLockWarning),s.on("keypress",this.showCapsLockWarning);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&e.val(parent.opener.TYPO3.configuration.username)}catch(e){}""===e.val()?e.focus():s.focus()}static isCapslockEnabled(e){const s=e||window.event;if(!s)return!1;let n=-1;s.which?n=s.which:s.keyCode&&(n=s.keyCode);let o=!1;return s.shiftKey?o=s.shiftKey:s.modifiers&&(o=!!(4&s.modifiers)),n>=65&&n<=90&&!o||n>=97&&n<=122&&o}}return new i}));