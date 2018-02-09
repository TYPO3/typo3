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
var __extends=this&&this.__extends||function(){var a=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(a,b){a.__proto__=b}||function(a,b){for(var c in b)b.hasOwnProperty(c)&&(a[c]=b[c])};return function(b,c){function d(){this.constructor=b}a(b,c),b.prototype=null===c?Object.create(c):(d.prototype=c.prototype,new d)}}();define(["require","exports","./InteractionRequest"],function(a,b,c){"use strict";var d=function(a){function b(b,c){return void 0===c&&(c=null),a.call(this,b,c)||this}return __extends(b,a),b.prototype.concerns=function(a){if(this===a)return!0;for(var b=this;b.parentRequest instanceof c;)if(b=b.parentRequest,b===a)return!0;return!1},b.prototype.concernsTypes=function(a){if(a.indexOf(this.type)!==-1)return!0;for(var b=this;b.parentRequest instanceof c;)if(b=b.parentRequest,a.indexOf(b.type)!==-1)return!0;return!1},b}(c);return d});