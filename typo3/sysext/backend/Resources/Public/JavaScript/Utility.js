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
define(["require","exports"],function(e,t){"use strict";return function(){function e(){}return e.isNumber=function(e){return!isNaN(parseFloat(e.toString()))&&isFinite(e)},e.getParameterFromUrl=function(e,t){if("function"!=typeof e.split)return"";var n=e.split("?"),r="";if(n.length>=2)for(var i=n.join("?"),u=encodeURIComponent(t)+"=",o=i.split(/[&;]/g),a=o.length;a-- >0;)if(-1!==o[a].lastIndexOf(u,0)){r=o[a].split("=")[1];break}return r},e.updateQueryStringParameter=function(e,t,n){var r=new RegExp("([?&])"+t+"=.*?(&|$)","i"),i=-1!==e.indexOf("?")?"&":"?";return e.match(r)?e.replace(r,"$1"+t+"="+n+"$2"):e+i+t+"="+n},e}()});