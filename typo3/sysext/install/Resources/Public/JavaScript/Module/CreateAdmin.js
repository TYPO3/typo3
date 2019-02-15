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
define(["require","exports","jquery","../Router","./PasswordStrength","TYPO3/CMS/Backend/Notification"],function(t,e,r,n,s,a){"use strict";return new(function(){function t(){this.selectorModalBody=".t3js-modal-body",this.selectorModuleContent=".t3js-module-content",this.selectorCreateForm="#t3js-createAdmin-form"}return t.prototype.initialize=function(t){var e=this;this.currentModal=t,this.getData(),t.on("submit",this.selectorCreateForm,function(t){t.preventDefault(),e.create()}),t.on("click",".t3-install-form-password-strength",function(t){s.initialize(".t3-install-form-password-strength")})},t.prototype.getData=function(){var t=this.currentModal.find(this.selectorModalBody);r.ajax({url:n.getUrl("createAdminGetData"),cache:!1,success:function(e){!0===e.success?t.empty().append(e.html):a.error("Something went wrong")},error:function(e){n.handleAjaxError(e,t)}})},t.prototype.create=function(){var t=this.currentModal.find(this.selectorModalBody),e=this.currentModal.find(this.selectorModuleContent).data("create-admin-token");r.ajax({url:n.getUrl(),method:"POST",data:{install:{action:"createAdmin",token:e,userName:this.currentModal.find(".t3js-createAdmin-user").val(),userPassword:this.currentModal.find(".t3js-createAdmin-password").val(),userPasswordCheck:this.currentModal.find(".t3js-createAdmin-password-check").val(),userSystemMaintainer:this.currentModal.find(".t3js-createAdmin-system-maintainer").is(":checked")?1:0}},cache:!1,success:function(t){!0===t.success&&Array.isArray(t.status)?t.status.forEach(function(t){2===t.severity?a.error(t.message):a.success(t.title)}):a.error("Something went wrong")},error:function(e){n.handleAjaxError(e,t)}}),this.currentModal.find(".t3js-createAdmin-user").val(""),this.currentModal.find(".t3js-createAdmin-password").val(""),this.currentModal.find(".t3js-createAdmin-password-check").val(""),this.currentModal.find(".t3js-createAdmin-system-maintainer").prop("checked",!1)},t}())});