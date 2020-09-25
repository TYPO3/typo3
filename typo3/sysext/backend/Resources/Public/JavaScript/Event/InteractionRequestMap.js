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
define(["require","exports"],(function(t,e){"use strict";return new(function(){function t(){this.assignments=[]}return t.prototype.attachFor=function(t,e){var r=this.getFor(t);null===r&&(r={request:t,deferreds:[]},this.assignments.push(r)),r.deferreds.push(e)},t.prototype.detachFor=function(t){var e=this.getFor(t);this.assignments=this.assignments.filter((function(t){return t===e}))},t.prototype.getFor=function(t){var e=null;return this.assignments.some((function(r){return r.request===t&&(e=r,!0)})),e},t.prototype.resolveFor=function(t){var e=this.getFor(t);return null!==e&&(e.deferreds.forEach((function(t){return t.resolve()})),this.detachFor(t),!0)},t.prototype.rejectFor=function(t){var e=this.getFor(t);return null!==e&&(e.deferreds.forEach((function(t){return t.reject()})),this.detachFor(t),!0)},t}())}));