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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../PasswordStrength","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(t,e,s,a,r,n,i,o){"use strict";return new class extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorAdminCreateButton=".t3js-createAdmin-create"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorAdminCreateButton,t=>{t.preventDefault(),this.create()}),t.on("click",".t3-install-form-password-strength",t=>{n.initialize(".t3-install-form-password-strength")})}getData(){const t=this.getModalBody();a.ajax({url:r.getUrl("createAdminGetData"),cache:!1,success:e=>{!0===e.success?(t.empty().append(e.html),i.setButtons(e.buttons)):o.error("Something went wrong")},error:e=>{r.handleAjaxError(e,t)}})}create(){const t=this.getModalBody(),e=this.getModuleContent().data("create-admin-token");a.ajax({url:r.getUrl(),method:"POST",data:{install:{action:"createAdmin",token:e,userName:this.findInModal(".t3js-createAdmin-user").val(),userPassword:this.findInModal(".t3js-createAdmin-password").val(),userPasswordCheck:this.findInModal(".t3js-createAdmin-password-check").val(),userSystemMaintainer:this.findInModal(".t3js-createAdmin-system-maintainer").is(":checked")?1:0}},cache:!1,success:t=>{!0===t.success&&Array.isArray(t.status)?t.status.forEach(t=>{2===t.severity?o.error(t.message):o.success(t.title)}):o.error("Something went wrong")},error:e=>{r.handleAjaxError(e,t)}}),this.findInModal(".t3js-createAdmin-user").val(""),this.findInModal(".t3js-createAdmin-password").val(""),this.findInModal(".t3js-createAdmin-password-check").val(""),this.findInModal(".t3js-createAdmin-system-maintainer").prop("checked",!1)}}});