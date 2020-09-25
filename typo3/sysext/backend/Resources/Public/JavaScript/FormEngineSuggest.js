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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Core/Contrib/jquery.autocomplete"],(function(e,t,a,n){"use strict";return function(){function e(e){var t=this;a((function(){t.initialize(e)}))}return e.prototype.initialize=function(e){var t=a(e).closest(".t3-form-suggest-container"),i=e.dataset.tablename,o=e.dataset.fieldname,r=e.dataset.field,s=parseInt(e.dataset.uid,10),d=parseInt(e.dataset.pid,10),l=e.dataset.datastructureidentifier,u=e.dataset.flexformsheetname,m=e.dataset.flexformfieldname,c=e.dataset.flexformcontainername,f=e.dataset.flexformcontainerfieldname,p=parseInt(e.dataset.minchars,10),g=TYPO3.settings.ajaxUrls.record_suggest,x={tableName:i,fieldName:o,uid:s,pid:d,dataStructureIdentifier:l,flexFormSheetName:u,flexFormFieldName:m,flexFormContainerName:c,flexFormContainerFieldName:f};a(e).autocomplete({serviceUrl:g,params:x,type:"POST",paramName:"value",dataType:"json",minChars:p,groupBy:"typeLabel",containerClass:"autocomplete-results",appendTo:t,forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,noSuggestionNotice:'<div class="autocomplete-info">No results</div>',minLength:p,preventBadQueries:!1,transformResult:function(e){return{suggestions:e.map((function(e){return{value:e.text,data:e}}))}},formatResult:function(e){return a("<div>").append(a('<a class="autocomplete-suggestion-link" href="#">'+e.data.sprite+e.data.text+"</a></div>").attr({"data-label":e.data.label,"data-table":e.data.table,"data-uid":e.data.uid})).html()},onSearchComplete:function(){t.addClass("open")},beforeRender:function(e){e.attr("style",""),t.addClass("open")},onHide:function(){t.removeClass("open")},onSelect:function(){var i,o;i=t.find(".autocomplete-selected a"),o="",o="select"===e.dataset.fieldtype?i.data("uid"):i.data("table")+"_"+i.data("uid"),n.setSelectOptionFromExternalSource(r,o,i.data("label"),i.data("label")),n.Validation.markFieldAsChanged(a(document.querySelector('input[name="'+r+'"]')))}})},e}()}));