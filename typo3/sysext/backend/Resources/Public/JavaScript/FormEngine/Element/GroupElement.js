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
var __extends=this&&this.__extends||function(){var e=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var r in t)t.hasOwnProperty(r)&&(e[r]=t[r])};return function(t,r){function n(){this.constructor=t}e(t,r),t.prototype=null===r?Object.create(r):(n.prototype=r.prototype,new n)}}();define(["require","exports","./AbstractSortableSelectItems","jquery","../../FormEngineSuggest"],function(e,t,r,n,o){"use strict";return function(e){function t(t){var r=e.call(this)||this;return r.element=null,n(function(){r.element=document.querySelector("#"+t),r.registerEventHandler(),r.registerSuggest()}),r}return __extends(t,e),t.prototype.registerEventHandler=function(){this.registerSortableEventHandler(this.element)},t.prototype.registerSuggest=function(){var e;null!==(e=this.element.closest(".t3js-formengine-field-item").querySelector(".t3-form-suggest"))&&new o(e)},t}(r.AbstractSortableSelectItems)});