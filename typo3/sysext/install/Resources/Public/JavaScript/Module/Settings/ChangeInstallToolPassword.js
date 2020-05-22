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
define(["require","exports","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../PasswordStrength","../AbstractInteractableModule"],(function(s,t,e,a,o,n,l,r){"use strict";class c extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorChangeButton=".t3js-changeInstallToolPassword-change"}initialize(s){this.currentModal=s,this.getData(),s.on("click",this.selectorChangeButton,s=>{s.preventDefault(),this.change()}),s.on("click",".t3-install-form-password-strength",()=>{l.initialize(".t3-install-form-password-strength")})}getData(){const s=this.getModalBody();new o(n.getUrl("changeInstallToolPasswordGetData")).get({cache:"no-cache"}).then(async t=>{const o=await t.resolve();!0===o.success?(s.empty().append(o.html),e.setButtons(o.buttons)):a.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{n.handleAjaxError(t,s)})}change(){this.setModalButtonsState(!1);const s=this.getModalBody(),t=this.getModuleContent().data("install-tool-token");new o(n.getUrl()).post({install:{action:"changeInstallToolPassword",token:t,password:this.findInModal(".t3js-changeInstallToolPassword-password").val(),passwordCheck:this.findInModal(".t3js-changeInstallToolPassword-password-check").val()}}).then(async s=>{const t=await s.resolve();!0===t.success&&Array.isArray(t.status)?t.status.forEach(s=>{a.showMessage(s.title,s.message,s.severity)}):a.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{n.handleAjaxError(t,s)}).finally(()=>{this.findInModal(".t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check").val(""),this.setModalButtonsState(!0)})}}return new c}));