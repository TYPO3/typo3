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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Backend/Popover","bootstrap"],(function(t,e,a,i,n){"use strict";a=__importDefault(a);class l{static findInvalidField(){return(0,a.default)(document).find(".tab-content ."+i.Validation.errorClass)}static attachButtonToModuleHeader(t){const e=(0,a.default)(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),i=(0,a.default)("<a />",{class:"btn btn-danger btn-sm hidden "+t.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append((0,a.default)("<span />",{class:"fa fa-fw fa-info"}));n.popover(i),e.prepend(i)}constructor(){this.toggleButtonClass="t3js-toggle-review-panel",this.labelSelector=".t3js-formengine-label",this.checkForReviewableField=()=>{const t=this,e=l.findInvalidField(),i=(0,a.default)("."+this.toggleButtonClass);if(e.length>0){const l=(0,a.default)("<div />",{class:"list-group"});e.each((function(){const e=(0,a.default)(this),i=e.find("[data-formengine-validation-rules]"),n=document.createElement("a");n.classList.add("list-group-item"),n.href="#",n.textContent=e.find(t.labelSelector).text(),n.addEventListener("click",e=>t.switchToField(e,i)),l.append(n)})),i.removeClass("hidden"),n.setOptions(i,{html:!0,content:l[0]})}else i.addClass("hidden"),n.hide(i)},this.switchToField=(t,e)=>{t.preventDefault(),e.parents('[id][role="tabpanel"]').each((function(){(0,a.default)('[aria-controls="'+(0,a.default)(this).attr("id")+'"]').tab("show")})),e.focus()},this.initialize()}initialize(){const t=this,e=(0,a.default)(document);(0,a.default)(()=>{l.attachButtonToModuleHeader(t)}),e.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)}}return new l}));