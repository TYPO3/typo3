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
import"bootstrap";import{AbstractInteractableModule as m}from"@typo3/install/module/abstract-interactable-module.js";import h from"@typo3/backend/modal.js";import i from"@typo3/backend/notification.js";import c from"@typo3/core/ajax/ajax-request.js";import{InfoBox as u}from"@typo3/install/renderable/info-box.js";import l from"@typo3/install/router.js";import d from"@typo3/core/event/regular-event.js";var a;(function(r){r.outputContainer=".t3js-mailTest-output",r.mailTestButton=".t3js-mailTest-execute"})(a||(a={}));class f extends m{initialize(s){super.initialize(s),this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js").then(()=>{this.getData()}),new d("click",e=>{e.preventDefault(),this.send()}).delegateTo(s,a.mailTestButton),new d("submit",e=>{e.preventDefault(),this.send()}).delegateTo(s,"form")}getData(){const s=this.getModalBody();new c(l.getUrl("mailTestGetData")).get({cache:"no-cache"}).then(async e=>{const t=await e.resolve();if(t.success===!0){s.innerHTML=t.html;const n=this.findInModal(a.outputContainer);t.messages&&Array.isArray(t.messages)&&t.messages.forEach(o=>{n.append(u.create(o.severity,o.title,o.message))}),t.sendPossible&&h.setButtons(t.buttons)}else i.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},e=>{l.handleAjaxError(e,s)})}send(){this.setModalButtonsState(!1);const s=this.getModuleContent().dataset.mailTestToken,e=this.findInModal(a.outputContainer);this.renderProgressBar(e),new c(l.getUrl()).post({install:{action:"mailTest",token:s,email:this.findInModal(".t3js-mailTest-email").value}}).then(async t=>{const n=await t.resolve();e.innerHTML="",Array.isArray(n.status)?n.status.forEach(o=>{e.innerHTML="",e.append(u.create(o.severity,o.title,o.message))}):i.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{i.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}).finally(()=>{this.setModalButtonsState(!0)})}}var p=new f;export{p as default};
