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
define(["require","exports","jquery","./Login"],function(e,n,s,i){"use strict";return new(function(){function e(){var n=this;this.resetPassword=function(){var e=s(n.options.passwordField);e.val()&&(s(i.options.useridentField).val(e.val()),e.val(""))},this.showCapsLockWarning=function(n){s(n.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!e.isCapslockEnabled(n))},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field"},i.options.submitHandler=this.resetPassword;var o=s(this.options.usernameField),r=s(this.options.passwordField);o.on("keypress",this.showCapsLockWarning),r.on("keypress",this.showCapsLockWarning);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&o.val(parent.opener.TYPO3.configuration.username)}catch(e){}""===o.val()?o.focus():r.focus()}return e.isCapslockEnabled=function(e){var n=e||window.event;if(!n)return!1;var s=-1;n.which?s=n.which:n.keyCode&&(s=n.keyCode);var i=!1;return n.shiftKey?i=n.shiftKey:n.modifiers&&(i=!!(4&n.modifiers)),s>=65&&s<=90&&!i||s>=97&&s<=122&&i},e}())});