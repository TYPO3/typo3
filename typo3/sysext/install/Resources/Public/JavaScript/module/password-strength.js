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
import r from"@typo3/core/event/regular-event.js";class o{initialize(s){new r("keyup",a=>{const e=a.target;this.checkPassword(e)}).bindTo(s),new r("blur",a=>{a.target.classList.remove("has-error","has-success","has-warning")}).bindTo(s),new r("focus",a=>{const e=a.target;this.checkPassword(e)}).bindTo(s)}checkPassword(s){const a=s.value,e=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),t=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),c=new RegExp("(?=.{8,}).*","g");s.classList.remove("has-error","has-success","has-warning"),a.length===0?s.classList.add("has-error"):c.test(a)?e.test(a)?s.classList.add("has-success"):(t.test(a),s.classList.add("has-warning")):s.classList.add("has-error")}}var n=new o;export{n as default};
