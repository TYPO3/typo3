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
define(["require","exports","jquery","../Router","TYPO3/CMS/Backend/Notification"],function(e,t,r,n,o){"use strict";return new(function(){function e(){this.selectorModalBody=".t3js-modal-body",this.selectorModuleContent=".t3js-features-content",this.selectorSaveTrigger=".t3js-features-save"}return e.prototype.initialize=function(e){var t=this;this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,function(e){e.preventDefault(),t.save()})},e.prototype.getContent=function(){var e=this.currentModal.find(this.selectorModalBody);r.ajax({url:n.getUrl("featuresGetContent"),cache:!1,success:function(t){!0===t.success&&"undefined"!==t.html&&t.html.length>0?e.empty().append(t.html):o.error("Something went wrong")},error:function(t){n.handleAjaxError(t,e)}})},e.prototype.save=function(){var e=this.currentModal.find(this.selectorModalBody),t=this.currentModal.find(this.selectorModuleContent).data("features-save-token"),s={};r(this.currentModal.find(this.selectorModuleContent+" form").serializeArray()).each(function(e,t){s[t.name]=t.value}),s["install[action]"]="featuresSave",s["install[token]"]=t,r.ajax({url:n.getUrl(),method:"POST",data:s,cache:!1,success:function(e){!0===e.success&&Array.isArray(e.status)?e.status.forEach(function(e){o.showMessage(e.title,e.message,e.severity)}):o.error("Something went wrong")},error:function(t){n.handleAjaxError(t,e)}})},e}())});