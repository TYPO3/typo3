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
class r{constructor(){this.assignments=[]}attachFor(t,s){let e=this.getFor(t);e===null&&(e={request:t,deferreds:[]},this.assignments.push(e)),e.deferreds.push(s)}detachFor(t){const s=this.getFor(t);this.assignments=this.assignments.filter(e=>e===s)}getFor(t){let s=null;return this.assignments.some(e=>e.request===t?(s=e,!0):!1),s}resolveFor(t){const s=this.getFor(t);return s===null?!1:(s.deferreds.forEach(e=>e.resolve()),this.detachFor(t),!0)}rejectFor(t){const s=this.getFor(t);return s===null?!1:(s.deferreds.forEach(e=>e.reject()),this.detachFor(t),!0)}}var n=new r;export{n as default};
