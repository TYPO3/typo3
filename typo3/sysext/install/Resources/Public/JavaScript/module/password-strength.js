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
import RegularEvent from"@typo3/core/event/regular-event.js";class PasswordStrength{initialize(s){new RegularEvent("keyup",(s=>{const e=s.target;this.checkPassword(e)})).bindTo(s),new RegularEvent("blur",(s=>{s.target.classList.remove("has-error","has-success","has-warning")})).bindTo(s),new RegularEvent("focus",(s=>{const e=s.target;this.checkPassword(e)})).bindTo(s)}checkPassword(s){const e=s.value,a=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),t=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),r=new RegExp("(?=.{8,}).*","g");s.classList.remove("has-error","has-success","has-warning"),0===e.length?s.classList.add("has-error"):r.test(e)?a.test(e)?s.classList.add("has-success"):(t.test(e),s.classList.add("has-warning")):s.classList.add("has-error")}}export default new PasswordStrength;