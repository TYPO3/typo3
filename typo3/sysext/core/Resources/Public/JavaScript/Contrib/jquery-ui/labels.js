import jQuery from"jquery";import"jquery-ui/version.js";let define=null;
/*!
 * jQuery UI Labels 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],e):e(jQuery)}((function(e){"use strict";return e.fn.labels=function(){var t,s,i,n,l;return this.length?this[0].labels&&this[0].labels.length?this.pushStack(this[0].labels):(n=this.eq(0).parents("label"),(i=this.attr("id"))&&(l=(t=this.eq(0).parents().last()).add(t.length?t.siblings():this.siblings()),s="label[for='"+e.escapeSelector(i)+"']",n=n.add(l.find(s).addBack(s))),this.pushStack(n)):this.pushStack([])}}));