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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.default=
/*! Based on https://www.promisejs.org/polyfills/promise-done-7.0.4.js */
class{static support(){"function"!=typeof Promise.prototype.done&&(Promise.prototype.done=function(t){return arguments.length?this.then.apply(this,arguments):Promise.prototype.then}),"function"!=typeof Promise.prototype.fail&&(Promise.prototype.fail=function(t){const e=arguments.length?this.catch.apply(this,arguments):Promise.prototype.catch;return e.catch((function(t){setTimeout((function(){throw t}),0)})),e})}}}));