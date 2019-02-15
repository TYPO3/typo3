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
define(["require","exports","jquery","../Router","../Renderable/ProgressBar","../Renderable/InfoBox","../Renderable/Severity","TYPO3/CMS/Backend/Notification","bootstrap"],function(e,t,r,n,s,o,i,a){"use strict";return new(function(){function e(){this.selectorModalBody=".t3js-modal-body",this.selectorGridderBadge=".t3js-environmentCheck-badge",this.selectorExecuteTrigger=".t3js-environmentCheck-execute",this.selectorOutputContainer=".t3js-environmentCheck-output"}return e.prototype.initialize=function(e){var t=this;this.currentModal=e,this.runTests(),e.on("click",this.selectorExecuteTrigger,function(e){e.preventDefault(),t.runTests()})},e.prototype.runTests=function(){var e=this,t=this.currentModal.find(this.selectorModalBody),c=r(this.selectorGridderBadge);c.text("").hide();var l=s.render(i.loading,"Loading...","");t.find(this.selectorOutputContainer).empty().append(l),r.ajax({url:n.getUrl("environmentCheckGetStatus"),cache:!1,success:function(n){t.empty().append(n.html);var s=0,i=0;!0===n.success&&"object"==typeof n.status?(r.each(n.status,function(r,n){Array.isArray(n)&&n.length>0&&n.forEach(function(r){1===r.severity&&s++,2===r.severity&&i++;var n=o.render(r.severity,r.title,r.message);t.find(e.selectorOutputContainer).append(n)})}),i>0?c.removeClass("label-warning").addClass("label-danger").text(i).show():s>0&&c.removeClass("label-error").addClass("label-warning").text(s).show()):a.error("Something went wrong")},error:function(e){n.handleAjaxError(e,t)}})},e}())});