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
import"bootstrap";import $ from"jquery";import FormEngine from"@typo3/backend/form-engine.js";import"@typo3/backend/element/icon-element.js";import Popover from"@typo3/backend/popover.js";class FormEngineReview{constructor(){this.toggleButtonClass="t3js-toggle-review-panel",this.labelSelector=".t3js-formengine-label",this.checkForReviewableField=()=>{const e=this,t=FormEngineReview.findInvalidField(),i=$("."+this.toggleButtonClass);if(t.length>0){const o=$("<div />",{class:"list-group"});t.each((function(){const t=$(this),i=t.find("[data-formengine-validation-rules]"),n=document.createElement("a");n.classList.add("list-group-item"),n.href="#",n.textContent=t.find(e.labelSelector).text(),n.addEventListener("click",(t=>e.switchToField(t,i))),o.append(n)})),i.removeClass("hidden"),Popover.setOptions(i,{html:!0,content:o[0]})}else i.addClass("hidden"),Popover.hide(i)},this.switchToField=(e,t)=>{e.preventDefault();e.currentTarget;t.parents('[id][role="tabpanel"]').each((function(){$('[aria-controls="'+$(this).attr("id")+'"]').tab("show")})),t.focus()},this.initialize()}static findInvalidField(){return $(document).find(".tab-content ."+FormEngine.Validation.errorClass)}static attachButtonToModuleHeader(e){const t=$(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),i=$("<a />",{class:"btn btn-danger btn-sm hidden "+e.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append($("<typo3-backend-icon/>",{identifier:"actions-info",size:"small"}));Popover.popover(i),t.prepend(i)}initialize(){const e=this,t=$(document);$((()=>{FormEngineReview.attachButtonToModuleHeader(e)})),t.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)}}export default new FormEngineReview;