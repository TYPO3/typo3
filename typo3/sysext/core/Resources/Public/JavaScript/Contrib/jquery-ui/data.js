import jQuery from"jquery";import"jquery-ui/version.js";let define=null;
/*!
 * jQuery UI :data 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],e):e(jQuery)}((function(e){"use strict";return e.extend(e.expr.pseudos,{data:e.expr.createPseudo?e.expr.createPseudo((function(r){return function(n){return!!e.data(n,r)}})):function(r,n,t){return!!e.data(r,t[3])}})}));