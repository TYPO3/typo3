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
import d from"@typo3/backend/modal.js";import l from"@typo3/backend/notification.js";import c from"@typo3/core/ajax/ajax-request.js";import o from"@typo3/install/router.js";import h from"@typo3/install/module/password-strength.js";import{AbstractInteractableModule as g}from"@typo3/install/module/abstract-interactable-module.js";import u from"@typo3/core/event/regular-event.js";var r;(function(i){i.changeButton=".t3js-changeInstallToolPassword-change"})(r||(r={}));class w extends g{initialize(s){super.initialize(s),this.getData(),new u("click",t=>{t.preventDefault(),this.change()}).delegateTo(s,r.changeButton)}getData(){const s=this.getModalBody();new c(o.getUrl("changeInstallToolPasswordGetData")).get({cache:"no-cache"}).then(async t=>{const e=await t.resolve();e.success===!0?(s.innerHTML=e.html,h.initialize(s.querySelector(".t3-install-form-password-strength")),d.setButtons(e.buttons)):l.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{o.handleAjaxError(t,s)})}change(){this.setModalButtonsState(!1);const s=this.getModalBody(),t=this.getModuleContent().dataset.installToolToken;new c(o.getUrl()).post({install:{action:"changeInstallToolPassword",token:t,password:this.findInModal(".t3js-changeInstallToolPassword-password").value,passwordCheck:this.findInModal(".t3js-changeInstallToolPassword-password-check").value}}).then(async e=>{const a=await e.resolve();a.success===!0&&Array.isArray(a.status)?a.status.forEach(n=>{l.showMessage(n.title,n.message,n.severity)}):l.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},e=>{o.handleAjaxError(e,s)}).finally(()=>{this.findInModal(".t3js-changeInstallToolPassword-password").value="",this.findInModal(".t3js-changeInstallToolPassword-password-check").value="",this.setModalButtonsState(!0)})}}var f=new w;export{f as default};
