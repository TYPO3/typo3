import jQuery from"jquery";import"jquery-ui/version.js";let define=null;
/*!
 * jQuery UI Focusable 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],e):e(jQuery)}((function(e){"use strict";return e.ui.focusable=function(i,t){var n,r,s,u,o,a=i.nodeName.toLowerCase();return"area"===a?(r=(n=i.parentNode).name,!(!i.href||!r||"map"!==n.nodeName.toLowerCase())&&((s=e("img[usemap='#"+r+"']")).length>0&&s.is(":visible"))):(/^(input|select|textarea|button|object)$/.test(a)?(u=!i.disabled)&&(o=e(i).closest("fieldset")[0])&&(u=!o.disabled):u="a"===a&&i.href||t,u&&e(i).is(":visible")&&function(e){var i=e.css("visibility");for(;"inherit"===i;)i=(e=e.parent()).css("visibility");return"visible"===i}(e(i)))},e.extend(e.expr.pseudos,{focusable:function(i){return e.ui.focusable(i,null!=e.attr(i,"tabindex"))}}),e.ui.focusable}));