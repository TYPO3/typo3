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
define(["require","exports","jquery","../Router","./PasswordStrength","TYPO3/CMS/Backend/Notification"],function(t,o,s,e,n,r){"use strict";return new(function(){function t(){this.selectorModalBody=".t3js-modal-body",this.selectorModuleContent=".t3js-module-content",this.selectorChangeForm="#t3js-changeInstallToolPassword-form",this.currentModal={}}return t.prototype.initialize=function(t){var o=this;this.currentModal=t,this.getData(),t.on("submit",this.selectorChangeForm,function(t){t.preventDefault(),o.change()}),t.on("click",".t3-install-form-password-strength",function(t){n.initialize(".t3-install-form-password-strength")})},t.prototype.getData=function(){var t=this.currentModal.find(this.selectorModalBody);s.ajax({url:e.getUrl("changeInstallToolPasswordGetData"),cache:!1,success:function(o){!0===o.success?t.empty().append(o.html):r.error("Something went wrong")},error:function(o){e.handleAjaxError(o,t)}})},t.prototype.change=function(){var t=this,o=this.currentModal.find(this.selectorModalBody),n=this.currentModal.find(this.selectorModuleContent).data("install-tool-token");s.ajax({url:e.getUrl(),method:"POST",data:{install:{action:"changeInstallToolPassword",token:n,password:this.currentModal.find(".t3js-changeInstallToolPassword-password").val(),passwordCheck:this.currentModal.find(".t3js-changeInstallToolPassword-password-check").val()}},cache:!1,success:function(t){!0===t.success&&Array.isArray(t.status)?t.status.forEach(function(t){r.showMessage("",t.message,t.severity)}):r.error("Something went wrong")},error:function(t){e.handleAjaxError(t,o)},complete:function(){t.currentModal.find(".t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check").val("")}})},t}())});