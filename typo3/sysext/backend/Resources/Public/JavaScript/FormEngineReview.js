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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","bootstrap"],function(a,b,c,d){"use strict";var e=function(){function a(){var b=this;this.checkForReviewableField=function(){var d=b,e=a.findInvalidField(),f=c("."+b.toggleButtonClass);if(e.length>0){var g=c("<div />",{class:"list-group"});e.each(function(){var a=c(this),b=a.find("[data-formengine-validation-rules]"),e=b.attr("id");"undefined"==typeof e&&(e=b.parent().children("[id]").first().attr("id")),g.append(c("<a />",{class:"list-group-item "+d.fieldListItemClass,"data-field-id":e,href:"#"}).text(a.find(d.labelSelector).text()))}),f.removeClass("hidden");var h=f.data("bs.popover");h&&(h.options.content=g.wrapAll("<div>").parent().html(),h.setContent(),h.$tip.addClass(h.options.placement))}else f.addClass("hidden").popover("hide")},this.switchToField=function(a){a.preventDefault();var b=c(a.currentTarget),d=b.data("fieldId"),e=c("#"+d);e.parents('[id][role="tabpanel"]').each(function(){c('[aria-controls="'+c(this).attr("id")+'"]').tab("show")}),e.focus()},this.toggleButtonClass="t3js-toggle-review-panel",this.fieldListItemClass="t3js-field-item",this.labelSelector=".t3js-formengine-label",this.initialize()}return a.findInvalidField=function(){return c(document).find(".tab-content ."+d.Validation.errorClass)},a.attachButtonToModuleHeader=function(a){var b=c(".t3js-module-docheader-bar-buttons").children().last().find('[role="toolbar"]'),d=c("<a />",{class:"btn btn-danger btn-sm hidden "+a.toggleButtonClass,href:"#",title:TYPO3.lang["buttons.reviewFailedValidationFields"]}).append(c("<span />",{class:"fa fa-fw fa-info"}));d.popover({container:"body",html:!0,placement:"bottom"}),b.prepend(d)},a.prototype.initialize=function(){var b=this,d=c(document);c(function(){a.attachButtonToModuleHeader(b)}),d.on("click","."+this.fieldListItemClass,this.switchToField),d.on("t3-formengine-postfieldvalidation",this.checkForReviewableField)},a}();return new e});