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
import $ from"jquery";import Login from"@typo3/backend/login.js";class UserPassLogin{constructor(){this.resetPassword=()=>{const s=$(this.options.passwordField);s.val()&&($(Login.options.useridentField).val(s.val()),s.val(""))},this.showCapsLockWarning=s=>{$(s.target).parent().parent().find(".t3js-login-alert-capslock").toggleClass("hidden",!UserPassLogin.isCapslockEnabled(s))},this.toggleCopyright=s=>{" "===s.key&&s.target.click()},this.options={passwordField:".t3js-login-password-field",usernameField:".t3js-login-username-field",copyrightLink:"t3js-login-copyright-link"},Login.options.submitHandler=this.resetPassword;const s=$(this.options.usernameField),e=$(this.options.passwordField),o=document.getElementsByClassName(this.options.copyrightLink)[0];s.on("keypress",this.showCapsLockWarning),e.on("keypress",this.showCapsLockWarning),o.addEventListener("keydown",this.toggleCopyright);try{parent.opener&&parent.opener.TYPO3&&parent.opener.TYPO3.configuration&&parent.opener.TYPO3.configuration.username&&s.val(parent.opener.TYPO3.configuration.username)}catch(s){}""===s.val()?s.focus():e.focus()}static isCapslockEnabled(s){const e=s||window.event;if(!e)return!1;let o=-1;e.which?o=e.which:e.keyCode&&(o=e.keyCode);let i=!1;return e.shiftKey?i=e.shiftKey:e.modifiers&&(i=!!(4&e.modifiers)),o>=65&&o<=90&&!i||o>=97&&o<=122&&i}}export default new UserPassLogin;