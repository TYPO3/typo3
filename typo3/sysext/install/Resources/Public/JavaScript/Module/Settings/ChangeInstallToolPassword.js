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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../PasswordStrength","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(t,s,a,e,o,r,n,l){"use strict";return new class extends a.AbstractInteractableModule{constructor(){super(...arguments),this.selectorChangeButton=".t3js-changeInstallToolPassword-change"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorChangeButton,t=>{t.preventDefault(),this.change()}),t.on("click",".t3-install-form-password-strength",()=>{r.initialize(".t3-install-form-password-strength")})}getData(){const t=this.getModalBody();e.ajax({url:o.getUrl("changeInstallToolPasswordGetData"),cache:!1,success:s=>{!0===s.success?(t.empty().append(s.html),n.setButtons(s.buttons)):l.error("Something went wrong")},error:s=>{o.handleAjaxError(s,t)}})}change(){const t=this.getModalBody(),s=this.getModuleContent().data("install-tool-token");e.ajax({url:o.getUrl(),method:"POST",data:{install:{action:"changeInstallToolPassword",token:s,password:this.findInModal(".t3js-changeInstallToolPassword-password").val(),passwordCheck:this.findInModal(".t3js-changeInstallToolPassword-password-check").val()}},cache:!1,success:t=>{!0===t.success&&Array.isArray(t.status)?t.status.forEach(t=>{l.showMessage("",t.message,t.severity)}):l.error("Something went wrong")},error:s=>{o.handleAjaxError(s,t)},complete:()=>{this.findInModal(".t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check").val("")}})}}});