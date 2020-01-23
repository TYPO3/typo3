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
define(["require","exports","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../PasswordStrength","../AbstractInteractableModule"],(function(t,s,a,e,o,n,r,l){"use strict";class c extends l.AbstractInteractableModule{constructor(){super(...arguments),this.selectorChangeButton=".t3js-changeInstallToolPassword-change"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorChangeButton,t=>{t.preventDefault(),this.change()}),t.on("click",".t3-install-form-password-strength",()=>{r.initialize(".t3-install-form-password-strength")})}getData(){const t=this.getModalBody();new o(n.getUrl("changeInstallToolPasswordGetData")).get({cache:"no-cache"}).then(async s=>{const o=await s.resolve();!0===o.success?(t.empty().append(o.html),a.setButtons(o.buttons)):e.error("Something went wrong")},s=>{n.handleAjaxError(s,t)})}change(){const t=this.getModalBody(),s=this.getModuleContent().data("install-tool-token");new o(n.getUrl()).post({install:{action:"changeInstallToolPassword",token:s,password:this.findInModal(".t3js-changeInstallToolPassword-password").val(),passwordCheck:this.findInModal(".t3js-changeInstallToolPassword-password-check").val()}}).then(async t=>{const s=await t.resolve();!0===s.success&&Array.isArray(s.status)?s.status.forEach(t=>{e.showMessage("",t.message,t.severity)}):e.error("Something went wrong")},s=>{n.handleAjaxError(s,t)}).finally(()=>{this.findInModal(".t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check").val("")})}}return new c}));