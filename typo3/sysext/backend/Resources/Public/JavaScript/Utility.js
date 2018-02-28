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
define(["require","exports"],function(t,e){"use strict";var r=new function(){this.isNumber=function(t){return!isNaN(parseFloat(t.toString()))&&isFinite(t)},this.getParameterFromUrl=function(t,e){if("function"!=typeof t.split)return"";var r=t.split("?"),i="";if(r.length>=2)for(var n=r.join("?"),a=encodeURIComponent(e)+"=",o=n.split(/[&;]/g),u=o.length;u-- >0;)if(-1!==o[u].lastIndexOf(a,0)){i=o[u].split("=")[1];break}return i},this.updateQueryStringParameter=function(t,e,r){var i=new RegExp("([?&])"+e+"=.*?(&|$)","i"),n=-1!==t.indexOf("?")?"&":"?";return t.match(i)?t.replace(i,"$1"+e+"="+r+"$2"):t+n+e+"="+r}};return TYPO3.Utility=r,r});