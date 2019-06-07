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
var __extends=this&&this.__extends||function(){var t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)e.hasOwnProperty(n)&&(t[n]=e[n])};return function(e,n){function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}();define(["require","exports","./AbstractInteractableModule","jquery","../Router","TYPO3/CMS/Backend/Notification"],function(t,e,n,r,o,i){"use strict";return new(function(t){function e(){return null!==t&&t.apply(this,arguments)||this}return __extends(e,t),e.prototype.initialize=function(t){this.currentModal=t,this.getData()},e.prototype.getData=function(){var t=this.getModalBody();r.ajax({url:o.getUrl("phpInfoGetData"),cache:!1,success:function(e){!0===e.success?t.empty().append(e.html):i.error("Something went wrong")},error:function(e){o.handleAjaxError(e,t)}})},e}(n.AbstractInteractableModule))});