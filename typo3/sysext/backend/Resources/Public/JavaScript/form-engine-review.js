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
import"bootstrap";import $ from"jquery";import FormEngine from"@typo3/backend/form-engine.js";class FormEngineReview{constructor(){this.checkForReviewableField=()=>{const t=this,e=FormEngineReview.findInvalidField(),i=$("."+this.toggleButtonClass);if(e.length>0){const n=$("<div />",{class:"list-group"});e.each((function(){const e=$(this),i=e.find("[data-formengine-validation-rules]");let o=i.attr("id");void 0===o&&(o=i.parent().children("[id]").first().attr("id")),n.append($("<a />",{class:"list-group-item "+t.fieldListItemClass,"data-field-id":o,href:"#"}).text(e.find(t.labelSelector).text()))})),i.removeClass("hidden");const o=i.data("bs.popover");o&&(o.options.html=!0,o.options.content=n.wrapAll("<div>").parent().html(),o.setContent(o.$tip),o.$tip.addClass(o.options.placement))}else i.addClass("hidden").popover("hide")},this.switchToField=t=>{t.preventDefault();const e=$(t.currentTarget).data("fieldId"),i=$("#"+e);i.parents('[id][role="tabpanel"]').each((function(){$('[aria-controls="'+$(this).attr("id")+'"]').tab("show")})),i.focus()},this.toggleButtonClass="t3js-toggle-review-panel",this.fieldListItemClass="t3js-field-item",this.labelSelector=".t3js-formengine-label",this.initialize()}static findInvalidField(){return $(document).find(".tab-content ."+FormEngine.Validation.errorClass)}static attachButtonToModuleHeader(t){const e=$(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),i=$("<a />",{class:"btn btn-danger btn-sm hidden "+t.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append($("<span />",{class:"fa fa-fw fa-info"}));i.popover({container:"body",html:!0,placement:"bottom"}),e.prepend(i)}initialize(){const t=this,e=$(document);$(()=>{FormEngineReview.attachButtonToModuleHeader(t)}),e.on("click","."+this.fieldListItemClass,this.switchToField),e.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)}}export default new FormEngineReview;