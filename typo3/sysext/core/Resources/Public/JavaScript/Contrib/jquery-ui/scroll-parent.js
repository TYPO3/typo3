import jQuery from"jquery";import"jquery-ui/version.js";let define=null;
/*!
 * jQuery UI Scroll Parent 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],e):e(jQuery)}((function(e){"use strict";return e.fn.scrollParent=function(t){var n=this.css("position"),o="absolute"===n,r=t?/(auto|scroll|hidden)/:/(auto|scroll)/,s=this.parents().filter((function(){var t=e(this);return(!o||"static"!==t.css("position"))&&r.test(t.css("overflow")+t.css("overflow-y")+t.css("overflow-x"))})).eq(0);return"fixed"!==n&&s.length?s:e(this[0].ownerDocument||document)}}));