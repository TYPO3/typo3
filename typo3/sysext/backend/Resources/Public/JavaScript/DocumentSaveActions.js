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
define(["require","exports","jquery","./Icons"],function(t,e,n,a){"use strict";return function(){function t(){var t=this;this.preSubmitCallbacks=[],n(function(){t.initializeSaveHandling()})}return t.getInstance=function(){return null===t.instance&&(t.instance=new t),t.instance},t.prototype.addPreSubmitCallback=function(t){if("function"!=typeof t)throw"callback must be a function.";this.preSubmitCallbacks.push(t)},t.prototype.initializeSaveHandling=function(){var t=this,e=!1,r=["button[form]",'button[name^="_save"]','a[data-name^="_save"]','button[name="CMD"][value^="save"]','a[data-name="CMD"][data-value^="save"]'].join(",");n(".t3js-module-docheader").on("click",r,function(r){if(!e){e=!0;for(var i=n(r.currentTarget),u=i.attr("form")||i.attr("data-form")||null,o=u?n("#"+u):i.closest("form"),l=i.data("name")||r.currentTarget.getAttribute("name"),s=i.data("value")||r.currentTarget.getAttribute("value"),c=n("<input />").attr("type","hidden").attr("name",l).attr("value",s),f=0;f<t.preSubmitCallbacks.length;++f)if(t.preSubmitCallbacks[f](r),r.isPropagationStopped())return e=!1,!1;o.append(c),o.on("submit",function(){if(o.find(".has-error").length>0)return e=!1,!1;var t,n=i.closest(".t3js-splitbutton");return n.length>0?(n.find("button").prop("disabled",!0),t=n.children().first()):(i.prop("disabled",!0),t=i),a.getIcon("spinner-circle-dark",a.sizes.small).done(function(e){t.find(".t3js-icon").replaceWith(e)}),!0}),"A"!==r.currentTarget.tagName&&!i.attr("form")||r.isDefaultPrevented()||(o.submit(),r.preventDefault())}return!0})},t.instance=null,t}()});