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
define(["require","exports","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../PasswordStrength","../AbstractInteractableModule"],(function(t,e,a,s,n,r,i,o){"use strict";class d extends o.AbstractInteractableModule{constructor(){super(...arguments),this.selectorAdminCreateButton=".t3js-createAdmin-create"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorAdminCreateButton,t=>{t.preventDefault(),this.create()}),t.on("click",".t3-install-form-password-strength",()=>{i.initialize(".t3-install-form-password-strength")})}getData(){const t=this.getModalBody();new n(r.getUrl("createAdminGetData")).get({cache:"no-cache"}).then(async e=>{const n=await e.resolve();!0===n.success?(t.empty().append(n.html),a.setButtons(n.buttons)):s.error("Something went wrong")},e=>{r.handleAjaxError(e,t)})}create(){const t=this.getModalBody(),e=this.getModuleContent().data("create-admin-token");new n(r.getUrl()).post({install:{action:"createAdmin",token:e,userName:this.findInModal(".t3js-createAdmin-user").val(),userPassword:this.findInModal(".t3js-createAdmin-password").val(),userPasswordCheck:this.findInModal(".t3js-createAdmin-password-check").val(),userEmail:this.findInModal(".t3js-createAdmin-email").val(),userSystemMaintainer:this.findInModal(".t3js-createAdmin-system-maintainer").is(":checked")?1:0}}).then(async t=>{const e=await t.resolve();!0===e.success&&Array.isArray(e.status)?e.status.forEach(t=>{2===t.severity?s.error(t.message):s.success(t.title)}):s.error("Something went wrong")},e=>{r.handleAjaxError(e,t)}),this.findInModal(".t3js-createAdmin-user").val(""),this.findInModal(".t3js-createAdmin-password").val(""),this.findInModal(".t3js-createAdmin-password-check").val(""),this.findInModal(".t3js-createAdmin-email").val(""),this.findInModal(".t3js-createAdmin-system-maintainer").prop("checked",!1)}}return new d}));