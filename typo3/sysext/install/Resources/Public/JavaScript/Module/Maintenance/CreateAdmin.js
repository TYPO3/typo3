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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../PasswordStrength","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(e,t,a,s,r,n,i,d){"use strict";return new class extends a.AbstractInteractableModule{constructor(){super(...arguments),this.selectorAdminCreateButton=".t3js-createAdmin-create"}initialize(e){this.currentModal=e,this.getData(),e.on("click",this.selectorAdminCreateButton,e=>{e.preventDefault(),this.create()}),e.on("click",".t3-install-form-password-strength",()=>{n.initialize(".t3-install-form-password-strength")})}getData(){const e=this.getModalBody();s.ajax({url:r.getUrl("createAdminGetData"),cache:!1,success:t=>{!0===t.success?(e.empty().append(t.html),i.setButtons(t.buttons)):d.error("Something went wrong")},error:t=>{r.handleAjaxError(t,e)}})}create(){const e=this.getModalBody(),t=this.getModuleContent().data("create-admin-token");s.ajax({url:r.getUrl(),method:"POST",data:{install:{action:"createAdmin",token:t,userName:this.findInModal(".t3js-createAdmin-user").val(),userPassword:this.findInModal(".t3js-createAdmin-password").val(),userPasswordCheck:this.findInModal(".t3js-createAdmin-password-check").val(),userEmail:this.findInModal(".t3js-createAdmin-email").val(),userSystemMaintainer:this.findInModal(".t3js-createAdmin-system-maintainer").is(":checked")?1:0}},cache:!1,success:e=>{!0===e.success&&Array.isArray(e.status)?e.status.forEach(e=>{2===e.severity?d.error(e.message):d.success(e.title)}):d.error("Something went wrong")},error:t=>{r.handleAjaxError(t,e)}}),this.findInModal(".t3js-createAdmin-user").val(""),this.findInModal(".t3js-createAdmin-password").val(""),this.findInModal(".t3js-createAdmin-password-check").val(""),this.findInModal(".t3js-createAdmin-email").val(""),this.findInModal(".t3js-createAdmin-system-maintainer").prop("checked",!1)}}});