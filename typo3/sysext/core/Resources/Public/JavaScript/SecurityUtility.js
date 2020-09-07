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
define(["require","exports"],function(e,t){"use strict";return function(){function e(e){void 0===e&&(e=document),this.documentRef=e}return e.prototype.getRandomHexValue=function(e){if(e<=0||e!==Math.ceil(e))throw new SyntaxError("Length must be a positive integer");var t=new Uint8Array(Math.ceil(e/2)),n=[];crypto.getRandomValues(t);for(var r=0;r<t.byteLength;r++)n[r]=t[r];return n.map(function(e){var t=e.toString(16);return 2===t.length?t:"0"+t}).join("").substr(0,e)},e.prototype.encodeHtml=function(e,t){void 0===t&&(t=!0);var n=this.createAnvil();return t||(e=e.replace(/&[#A-Za-z0-9]+;/g,function(e){return n.innerHTML=e,n.innerText})),n.innerText=e,n.innerHTML},e.prototype.debug=function(e){e!==this.encodeHtml(e)&&console.warn("XSS?!",e)},e.prototype.createAnvil=function(){return this.documentRef.createElement("span")},e}()});