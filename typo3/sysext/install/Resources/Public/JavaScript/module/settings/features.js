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
import{AbstractInteractableModule as h}from"@typo3/install/module/abstract-interactable-module.js";import d from"@typo3/backend/modal.js";import i from"@typo3/backend/notification.js";import u from"@typo3/core/ajax/ajax-request.js";import o from"@typo3/install/router.js";import f from"@typo3/core/event/regular-event.js";var l;(function(c){c.saveTrigger=".t3js-features-save"})(l||(l={}));class g extends h{initialize(t){super.initialize(t),this.getContent(),new f("click",s=>{s.preventDefault(),this.save()}).delegateTo(t,l.saveTrigger)}getContent(){const t=this.getModalBody();new u(o.getUrl("featuresGetContent")).get({cache:"no-cache"}).then(async s=>{const e=await s.resolve();e.success===!0&&e.html!=="undefined"&&e.html.length>0?(t.innerHTML=e.html,d.setButtons(e.buttons)):i.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},s=>{o.handleAjaxError(s,t)})}save(){this.setModalButtonsState(!1);const t=this.getModalBody(),s=this.getModuleContent().dataset.featuresSaveToken,e=new FormData(this.findInModal("form"));e.set("install[action]","featuresSave"),e.set("install[token]",s??""),new u(o.getUrl()).post(e).then(async a=>{const n=await a.resolve();n.success===!0&&Array.isArray(n.status)?(n.status.forEach(r=>{i.showMessage(r.title,r.message,r.severity)}),this.getContent()):i.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},a=>{o.handleAjaxError(a,t)}).finally(()=>{this.setModalButtonsState(!0)})}}var m=new g;export{m as default};
