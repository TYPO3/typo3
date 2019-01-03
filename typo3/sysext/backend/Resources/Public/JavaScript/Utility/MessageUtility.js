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
define(["require","exports"],function(e,n){"use strict";Object.defineProperty(n,"__esModule",{value:!0});var i=function(){function e(){}return e.getUrl=function(){return new URL(window.location.href).origin},e.verifyOrigin=function(n){return e.getUrl()===n},e.send=function(n,i){void 0===i&&(i=window),i.postMessage(n,e.getUrl())},e}();n.MessageUtility=i});