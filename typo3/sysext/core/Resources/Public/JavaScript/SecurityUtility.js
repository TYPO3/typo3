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
define(["require","exports"],function(e,n){"use strict";return function(){function e(e){void 0===e&&(e=document),this.documentRef=e}return e.prototype.encodeHtml=function(e,n){void 0===n&&(n=!0);var t=this.createAnvil();return n||(e=e.replace(/&[#A-Za-z0-9]+;/g,function(e){return t.innerHTML=e,t.innerText})),t.innerText=e,t.innerHTML},e.prototype.debug=function(e){e!==this.encodeHtml(e)&&console.warn("XSS?!",e)},e.prototype.createAnvil=function(){return this.documentRef.createElement("span")},e}()});