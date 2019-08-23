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
define(["require","exports"],function(t,n){"use strict";return function(){function t(){}return t.trimExplode=function(t,n){return n.split(t).map(function(t){return t.trim()}).filter(function(t){return""!==t})},t.intExplode=function(t,n,r){return void 0===r&&(r=!1),n.split(t).map(function(t){return parseInt(t,10)}).filter(function(t){return!isNaN(t)||r&&0===t})},t.isNumber=function(t){return!isNaN(parseFloat(t.toString()))&&isFinite(t)},t.getParameterFromUrl=function(t,n){if("function"!=typeof t.split)return"";var r=t.split("?"),e="";if(r.length>=2)for(var i=r.join("?"),u=encodeURIComponent(n)+"=",o=i.split(/[&;]/g),a=o.length;a-- >0;)if(-1!==o[a].lastIndexOf(u,0)){e=o[a].split("=")[1];break}return e},t.updateQueryStringParameter=function(t,n,r){var e=new RegExp("([?&])"+n+"=.*?(&|$)","i"),i=-1!==t.indexOf("?")?"&":"?";return t.match(e)?t.replace(e,"$1"+n+"="+r+"$2"):t+i+n+"="+r},t.convertFormToObject=function(t){for(var n={},r=t.querySelectorAll("input, select, textarea"),e=0;e<r.length;++e){var i=r[e],u=i.name,o=i.value;u&&(n[u]=o)}return n},t}()});