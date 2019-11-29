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
define(["require","exports"],(function(e,s){"use strict";return new class{constructor(){this.assignments=[]}attachFor(e,s){let t=this.getFor(e);null===t&&(t={request:e,deferreds:[]},this.assignments.push(t)),t.deferreds.push(s)}detachFor(e){const s=this.getFor(e);this.assignments=this.assignments.filter(e=>e===s)}getFor(e){let s=null;return this.assignments.some(t=>t.request===e&&(s=t,!0)),s}resolveFor(e){const s=this.getFor(e);return null!==s&&(s.deferreds.forEach(e=>e.resolve()),this.detachFor(e),!0)}rejectFor(e){const s=this.getFor(e);return null!==s&&(s.deferreds.forEach(e=>e.reject()),this.detachFor(e),!0)}}}));