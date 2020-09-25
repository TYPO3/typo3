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
define(["require","exports","jquery","./Icons"],(function(t,a,e,n){"use strict";return new(function(){function t(){var t=this;this.preSubmitCallbacks=[],e((function(){t.initializeSaveHandling()}))}return t.prototype.addPreSubmitCallback=function(t){if("function"!=typeof t)throw"callback must be a function.";this.preSubmitCallbacks.push(t)},t.prototype.initializeSaveHandling=function(){var t=this,a=!1,r=["button[form]",'button[name^="_save"]','a[data-name^="_save"]','button[name="CMD"][value^="save"]','a[data-name="CMD"][data-value^="save"]','button[name^="_translation_save"]','a[data-name^="_translation_save"]','button[name="CMD"][value^="_translation_save"]','a[data-name="CMD"][data-value^="_translation_save"]'].join(",");e(".t3js-module-docheader").on("click",r,(function(r){if(!a){a=!0;for(var i=e(r.currentTarget),u=i.attr("form")||i.attr("data-form")||null,o=u?e("#"+u):i.closest("form"),l=i.data("name")||r.currentTarget.getAttribute("name"),s=i.data("value")||r.currentTarget.getAttribute("value"),c=e("<input />").attr("type","hidden").attr("name",l).attr("value",s),d=0;d<t.preSubmitCallbacks.length;++d)if(t.preSubmitCallbacks[d](r),r.isPropagationStopped())return a=!1,!1;o.append(c),o.on("submit",(function(){if(o.find(".has-error").length>0)return a=!1,!1;var t,e=i.closest(".t3js-splitbutton");return e.length>0?(e.find("button").prop("disabled",!0),t=e.children().first()):(i.prop("disabled",!0),t=i),n.getIcon("spinner-circle-dark",n.sizes.small).done((function(a){t.find(".t3js-icon").replaceWith(a)})),!0})),"A"!==r.currentTarget.tagName&&!i.attr("form")||r.isDefaultPrevented()||(o.submit(),r.preventDefault())}return!0}))},t}())}));