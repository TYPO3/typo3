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
define(["require","exports"],function(a,b){"use strict";var c=function(){function a(){this.isNumber=function(a){return!isNaN(parseFloat(a.toString()))&&isFinite(a)},this.getParameterFromUrl=function(a,b){if("function"!=typeof a.split)return"";var c=a.split("?"),d="";if(c.length>=2)for(var e=c.join("?"),f=encodeURIComponent(b)+"=",g=e.split(/[&;]/g),h=g.length;h-- >0;)if(g[h].lastIndexOf(f,0)!==-1){d=g[h].split("=")[1];break}return d},this.updateQueryStringParameter=function(a,b,c){var d=new RegExp("([?&])"+b+"=.*?(&|$)","i"),e=a.indexOf("?")!==-1?"&":"?";return a.match(d)?a.replace(d,"$1"+b+"="+c+"$2"):a+e+b+"="+c}}return a}(),d=new c;return TYPO3.Utility=d,d});