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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","bootstrap"],(function(t,e,i,a){"use strict";class n{constructor(){this.checkForReviewableField=()=>{const t=this,e=n.findInvalidField(),a=i("."+this.toggleButtonClass);if(e.length>0){const n=i("<div />",{class:"list-group"});e.each((function(){const e=i(this),a=e.find("[data-formengine-validation-rules]");let s=a.attr("id");void 0===s&&(s=a.parent().children("[id]").first().attr("id")),n.append(i("<a />",{class:"list-group-item "+t.fieldListItemClass,"data-field-id":s,href:"#"}).text(e.find(t.labelSelector).text()))})),a.removeClass("hidden");const s=a.data("bs.popover");s&&(s.options.content=n.wrapAll("<div>").parent().html(),s.setContent(),s.$tip.addClass(s.options.placement))}else a.addClass("hidden").popover("hide")},this.switchToField=t=>{t.preventDefault();const e=i(t.currentTarget).data("fieldId"),a=i("#"+e);a.parents('[id][role="tabpanel"]').each((function(){i('[aria-controls="'+i(this).attr("id")+'"]').tab("show")})),a.focus()},this.toggleButtonClass="t3js-toggle-review-panel",this.fieldListItemClass="t3js-field-item",this.labelSelector=".t3js-formengine-label",this.initialize()}static findInvalidField(){return i(document).find(".tab-content ."+a.Validation.errorClass)}static attachButtonToModuleHeader(t){const e=i(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),a=i("<a />",{class:"btn btn-danger btn-sm hidden "+t.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append(i("<span />",{class:"fa fa-fw fa-info"}));a.popover({container:"body",html:!0,placement:"bottom"}),e.prepend(a)}initialize(){const t=this,e=i(document);i(()=>{n.attachButtonToModuleHeader(t)}),e.on("click","."+this.fieldListItemClass,this.switchToField),e.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)}}return new n}));