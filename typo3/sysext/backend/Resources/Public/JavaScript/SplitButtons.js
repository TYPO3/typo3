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
define(["require","exports","jquery","./Icons"],function(a,b,c,d){"use strict";var e=function(){function a(){var a=this;this.preSubmitCallbacks=[],c(function(){a.initializeSaveHandling()})}return a.prototype.addPreSubmitCallback=function(a){if("function"!=typeof a)throw"callback must be a function.";this.preSubmitCallbacks.push(a)},a.prototype.initializeSaveHandling=function(){var a=this,b=!1,e=["button[form]",'button[name^="_save"]','a[data-name^="_save"]','button[name="CMD"][value^="save"]','a[data-name="CMD"][data-value^="save"]','button[name^="_translation_save"]','a[data-name^="_translation_save"]','button[name="CMD"][value^="_translation_save"]','a[data-name="CMD"][data-value^="_translation_save"]'].join(",");c(".t3js-module-docheader").on("click",e,function(e){if(!b){b=!0;for(var f=c(e.currentTarget),g=f.attr("form")||f.attr("data-form")||null,h=g?c("#"+g):f.closest("form"),i=f.data("name")||e.currentTarget.getAttribute("name"),j=f.data("value")||e.currentTarget.getAttribute("value"),k=c("<input />").attr("type","hidden").attr("name",i).attr("value",j),l=0;l<a.preSubmitCallbacks.length;++l)if(a.preSubmitCallbacks[l](e),e.isPropagationStopped())return b=!1,!1;h.append(k),h.on("submit",function(){if(h.find(".has-error").length>0)return b=!1,!1;var a,c=f.closest(".t3js-splitbutton");return c.length>0?(c.find("button").prop("disabled",!0),a=c.children().first()):(f.prop("disabled",!0),a=f),d.getIcon("spinner-circle-dark",d.sizes.small).done(function(b){a.find(".t3js-icon").replaceWith(b)}),!0}),"A"!==e.currentTarget.tagName&&!f.attr("form")||e.isDefaultPrevented()||(h.submit(),e.preventDefault())}return!0})},a}();return new e});