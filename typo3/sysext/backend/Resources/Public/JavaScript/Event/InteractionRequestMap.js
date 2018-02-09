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
define(["require","exports"],function(a,b){"use strict";var c=function(){function a(){this.assignments=[]}return a.prototype.attachFor=function(a,b){var c=this.getFor(a);null===c&&(c={request:a,deferreds:[]},this.assignments.push(c)),c.deferreds.push(b)},a.prototype.detachFor=function(a){var b=this.getFor(a);this.assignments=this.assignments.filter(function(a){return a===b})},a.prototype.getFor=function(a){var b=null;return this.assignments.some(function(c){return c.request===a&&(b=c,!0)}),b},a.prototype.resolveFor=function(a){var b=this.getFor(a);return null!==b&&(b.deferreds.forEach(function(a){return a.resolve()}),this.detachFor(a),!0)},a.prototype.rejectFor=function(a){var b=this.getFor(a);return null!==b&&(b.deferreds.forEach(function(a){return a.reject()}),this.detachFor(a),!0)},a}();return new c});