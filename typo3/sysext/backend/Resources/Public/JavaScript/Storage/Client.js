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
define(["require","exports"],function(t,e){"use strict";return new function(){var t=this;this.get=function(t){return localStorage.getItem("t3-"+t)},this.set=function(t,e){localStorage.setItem("t3-"+t,e)},this.unset=function(t){localStorage.removeItem("t3-"+t)},this.clear=function(){localStorage.clear()},this.isset=function(e){var n=t.get(e);return null!=n}}});