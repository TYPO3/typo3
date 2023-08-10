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
import RegularEvent from"@typo3/core/event/regular-event.js";class PasswordStrength{initialize(e){new RegularEvent("keyup",(e=>{const t=e.target;this.checkPassword(t)})).bindTo(e),new RegularEvent("blur",(e=>{e.target.removeAttribute("style")})).bindTo(e),new RegularEvent("focus",(e=>{const t=e.target;this.checkPassword(t)})).bindTo(e)}checkPassword(e){const t=e.value,r=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),o=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),s=new RegExp("(?=.{8,}).*","g");0===t.length?e.setAttribute("style","background-color:#FBB19B; border:1px solid #DC4C42"):s.test(t)?r.test(t)?e.setAttribute("style","background-color:#CDEACA; border:1px solid #58B548"):(o.test(t),e.setAttribute("style","background-color:#FBFFB3; border:1px solid #C4B70D")):e.setAttribute("style","background-color:#FBB19B; border:1px solid #DC4C42")}}export default new PasswordStrength;