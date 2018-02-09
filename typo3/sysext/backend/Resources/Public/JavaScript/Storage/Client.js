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
define(["require","exports"],function(a,b){"use strict";var c=function(){function a(){var a=this;this.get=function(a){return localStorage.getItem("t3-"+a)},this.set=function(a,b){localStorage.setItem("t3-"+a,b)},this.unset=function(a){localStorage.removeItem("t3-"+a)},this.clear=function(){localStorage.clear()},this.isset=function(b){var c=a.get(b);return"undefined"!=typeof c&&null!==c}}return a}();return new c});