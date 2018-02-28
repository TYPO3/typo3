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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","bootstrap"],function(t,e,i,n){"use strict";return new(function(){function t(){var e=this;this.checkForReviewableField=function(){var n=e,a=t.findInvalidField(),o=i("."+e.toggleButtonClass);if(a.length>0){var l=i("<div />",{class:"list-group"});a.each(function(){var t=i(this),e=t.find("[data-formengine-validation-rules]"),a=e.attr("id");void 0===a&&(a=e.parent().children("[id]").first().attr("id")),l.append(i("<a />",{class:"list-group-item "+n.fieldListItemClass,"data-field-id":a,href:"#"}).text(t.find(n.labelSelector).text()))}),o.removeClass("hidden");var s=o.data("bs.popover");s&&(s.options.content=l.wrapAll("<div>").parent().html(),s.setContent(),s.$tip.addClass(s.options.placement))}else o.addClass("hidden").popover("hide")},this.switchToField=function(t){t.preventDefault();var e=i(t.currentTarget).data("fieldId"),n=i("#"+e);n.parents('[id][role="tabpanel"]').each(function(){i('[aria-controls="'+i(this).attr("id")+'"]').tab("show")}),n.focus()},this.toggleButtonClass="t3js-toggle-review-panel",this.fieldListItemClass="t3js-field-item",this.labelSelector=".t3js-formengine-label",this.initialize()}return t.findInvalidField=function(){return i(document).find(".tab-content ."+n.Validation.errorClass)},t.attachButtonToModuleHeader=function(t){var e=i(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),n=i("<a />",{class:"btn btn-danger btn-sm hidden "+t.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append(i("<span />",{class:"fa fa-fw fa-info"}));n.popover({container:"body",html:!0,placement:"bottom"}),e.prepend(n)},t.prototype.initialize=function(){var e=this,n=i(document);i(function(){t.attachButtonToModuleHeader(e)}),n.on("click","."+this.fieldListItemClass,this.switchToField),n.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)},t}())});