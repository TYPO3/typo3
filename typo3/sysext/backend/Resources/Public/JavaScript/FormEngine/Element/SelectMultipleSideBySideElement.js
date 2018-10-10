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
var __extends=this&&this.__extends||function(){var e=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var n in t)t.hasOwnProperty(n)&&(e[n]=t[n])};return function(t,n){function r(){this.constructor=t}e(t,n),t.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}();define(["require","exports","./AbstractSortableSelectItems","jquery","TYPO3/CMS/Backend/FormEngine","./Extra/SelectBoxFilter"],function(e,t,n,r,o,i){"use strict";return function(e){function t(t,n){var o=e.call(this)||this;return o.selectedOptionsElement=null,o.availableOptionsElement=null,r(function(){o.selectedOptionsElement=document.querySelector("#"+t),o.availableOptionsElement=document.querySelector("#"+n),o.registerEventHandler()}),o}return __extends(t,e),t.prototype.registerEventHandler=function(){this.registerSortableEventHandler(this.selectedOptionsElement),this.availableOptionsElement.addEventListener("click",function(e){var t=e.currentTarget,n=t.dataset.relatedfieldname;if(n){var i=t.dataset.exclusiveValues,l=t.querySelectorAll("option:checked");l.length>0&&Array.from(l).forEach(function(e){o.setSelectOptionFromExternalSource(n,e.value,e.textContent,e.getAttribute("title"),i,r(e))})}}),new i(this.availableOptionsElement)},t}(n.AbstractSortableSelectItems)});