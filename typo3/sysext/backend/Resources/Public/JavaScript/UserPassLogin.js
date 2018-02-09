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
define(["require","exports","jquery","./Login"],function(a,b,c,d){"use strict";var e=function(){function a(){var b=this;this.resetPassword=function(){var a=c(b.options.passwordField);a.val()&&(c(d.options.useridentField).val(a.val()),a.val(""))},this.showCapsLockWarning=function(b){c(b.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!a.isCapslockEnabled(b))},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field"},d.options.submitHandler=this.resetPassword;var e=c(this.options.usernameField),f=c(this.options.passwordField);e.on("keypress",this.showCapsLockWarning),f.on("keypress",this.showCapsLockWarning);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&e.val(parent.opener.TYPO3.configuration.username)}catch(a){}""===e.val()?e.focus():f.focus()}return a.isCapslockEnabled=function(a){var b=a?a:window.event;if(!b)return!1;var c=-1;b.which?c=b.which:b.keyCode&&(c=b.keyCode);var d=!1;return b.shiftKey?d=b.shiftKey:b.modifiers&&(d=!!(4&b.modifiers)),c>=65&&c<=90&&!d||c>=97&&c<=122&&d},a}();return new e});