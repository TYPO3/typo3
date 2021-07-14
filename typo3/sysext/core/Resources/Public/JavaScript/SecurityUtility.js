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
define(["require","exports"],(function(t,e){"use strict";return function(){function t(t){void 0===t&&(t=document),this.documentRef=t}return t.prototype.getRandomHexValue=function(t){if(t<=0||t!==Math.ceil(t))throw new SyntaxError("Length must be a positive integer");var e=new Uint8Array(Math.ceil(t/2)),n=[];crypto.getRandomValues(e);for(var r=0;r<e.byteLength;r++)n[r]=e[r];return n.map((function(t){var e=t.toString(16);return 2===e.length?e:"0"+e})).join("").substr(0,t)},t.prototype.encodeHtml=function(t,e){void 0===e&&(e=!0);var n=this.createAnvil();return e||(t=t.replace(/&[#A-Za-z0-9]+;/g,(function(t){return n.innerHTML=t,n.innerText}))),n.innerText=t,n.innerHTML.replace(/"/g,"&quot;").replace(/'/g,"&apos;")},t.prototype.stripHtml=function(t){return(new DOMParser).parseFromString(t,"text/html").body.textContent||""},t.prototype.debug=function(t){t!==this.encodeHtml(t)&&console.warn("XSS?!",t)},t.prototype.createAnvil=function(){return this.documentRef.createElement("span")},t}()}));