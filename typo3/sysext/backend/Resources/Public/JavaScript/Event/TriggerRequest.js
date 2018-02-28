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
var __extends=this&&this.__extends||function(){var t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)e.hasOwnProperty(n)&&(t[n]=e[n])};return function(e,n){function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}();define(["require","exports","./InteractionRequest"],function(t,e,n){"use strict";return function(t){function e(e,n){return void 0===n&&(n=null),t.call(this,e,n)||this}return __extends(e,t),e.prototype.concerns=function(t){if(this===t)return!0;for(var e=this;e.parentRequest instanceof n;)if((e=e.parentRequest)===t)return!0;return!1},e.prototype.concernsTypes=function(t){if(-1!==t.indexOf(this.type))return!0;for(var e=this;e.parentRequest instanceof n;)if(e=e.parentRequest,-1!==t.indexOf(e.type))return!0;return!1},e}(n)});