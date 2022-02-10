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
import $ from"jquery";class PasswordStrength{initialize(r){$(document).on("keyup",r,r=>{const t=$(r.currentTarget),e=t.val(),o=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),a=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),s=new RegExp("(?=.{8,}).*","g");0===e.length?t.attr("style","background-color:#FBB19B; border:1px solid #DC4C42"):s.test(e)?o.test(e)?t.attr("style","background-color:#CDEACA; border:1px solid #58B548"):(a.test(e),t.attr("style","background-color:#FBFFB3; border:1px solid #C4B70D")):t.attr("style","background-color:#FBB19B; border:1px solid #DC4C42")})}}export default new PasswordStrength;