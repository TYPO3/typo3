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
define(["require","exports","jquery","../Router","../Renderable/ProgressBar","../Renderable/Severity","../Renderable/InfoBox","TYPO3/CMS/Backend/Notification","bootstrap"],function(t,e,r,o,n,s,i,a){"use strict";return new(function(){function t(){this.selectorModalBody=".t3js-modal-body",this.selectorModuleContent=".t3js-module-content",this.selectorForm="#t3js-mailTest-form",this.selectorOutputContainer=".t3js-mailTest-output"}return t.prototype.initialize=function(t){var e=this;this.currentModal=t,this.getData(),t.on("submit",this.selectorForm,function(t){t.preventDefault(),e.send()})},t.prototype.getData=function(){var t=this.currentModal.find(this.selectorModalBody);r.ajax({url:o.getUrl("mailTestGetData"),cache:!1,success:function(e){!0===e.success?t.empty().append(e.html):a.error("Something went wrong")},error:function(e){o.handleAjaxError(e,t)}})},t.prototype.send=function(){var t=this.currentModal.find(this.selectorModuleContent).data("mail-test-token"),e=this.currentModal.find(this.selectorOutputContainer),l=n.render(s.loading,"Loading...","");e.empty().html(l),r.ajax({url:o.getUrl(),method:"POST",data:{install:{action:"mailTest",token:t,email:this.currentModal.find(".t3js-mailTest-email").val()}},cache:!1,success:function(t){e.empty(),!0===t.success&&Array.isArray(t.status)?t.status.forEach(function(t){var r=i.render(t.severity,t.title,t.message);e.html(r)}):a.error("Something went wrong")},error:function(){a.error("Something went wrong")}})},t}())});