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
import $ from"jquery";import"jquery/autocomplete.js";import FormEngine from"@typo3/backend/form-engine.js";class FormEngineSuggest{constructor(e){$(()=>{this.initialize(e)})}initialize(e){const t=e.closest(".t3-form-suggest-container"),a=e.dataset.tablename,o=e.dataset.fieldname,n=e.dataset.field,s=parseInt(e.dataset.uid,10),i=parseInt(e.dataset.pid,10),r=e.dataset.datastructureidentifier,l=e.dataset.flexformsheetname,d=e.dataset.flexformfieldname,m=e.dataset.flexformcontainername,u=e.dataset.flexformcontainerfieldname,c=parseInt(e.dataset.minchars,10),p=TYPO3.settings.ajaxUrls.record_suggest,f={tableName:a,fieldName:o,uid:s,pid:i,dataStructureIdentifier:r,flexFormSheetName:l,flexFormFieldName:d,flexFormContainerName:m,flexFormContainerFieldName:u};$(e).autocomplete({serviceUrl:p,params:f,type:"POST",paramName:"value",dataType:"json",minChars:c,groupBy:"typeLabel",containerClass:"autocomplete-results",appendTo:t,forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,noSuggestionNotice:'<div class="autocomplete-info">No results</div>',minLength:c,preventBadQueries:!1,transformResult:e=>({suggestions:e.map(e=>({value:e.text,data:e}))}),formatResult:e=>$("<div>").append($('<a class="autocomplete-suggestion-link" href="#">'+e.data.sprite+e.data.text+"</a></div>").attr({"data-label":e.data.label,"data-table":e.data.table,"data-uid":e.data.uid})).html(),onSearchComplete:function(){t.classList.add("open")},beforeRender:function(e){e.attr("style",""),t.classList.add("open")},onHide:function(){t.classList.remove("open")},onSelect:function(){!function(t){let a="";a="select"===e.dataset.fieldtype?t.dataset.uid:t.dataset.table+"_"+t.dataset.uid,FormEngine.setSelectOptionFromExternalSource(n,a,t.dataset.label,t.dataset.label),FormEngine.Validation.markFieldAsChanged($(document.querySelector('input[name="'+n+'"]')))}(t.querySelector(".autocomplete-selected a"))}})}}export default FormEngineSuggest;