import jQuery from"jquery";import"jquery-ui/version.js";import"jquery-ui/focusable.js";let define=null;
/*!
 * jQuery UI Tabbable 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version","./focusable"],e):e(jQuery)}((function(e){"use strict";return e.extend(e.expr.pseudos,{tabbable:function(u){var r=e.attr(u,"tabindex"),n=null!=r;return(!n||r>=0)&&e.ui.focusable(u,n)}})}));