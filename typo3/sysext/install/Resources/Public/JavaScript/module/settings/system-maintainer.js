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
import"bootstrap";import{AbstractInteractableModule as d}from"@typo3/install/module/abstract-interactable-module.js";import m from"@typo3/backend/modal.js";import c from"@typo3/backend/notification.js";import u from"@typo3/core/ajax/ajax-request.js";import a from"@typo3/install/router.js";import h from"@typo3/core/event/regular-event.js";var r;(function(i){i.writeTrigger=".t3js-systemMaintainer-write",i.selectPureField=".t3js-systemMaintainer-select-pure"})(r||(r={}));class g extends d{initialize(e){super.initialize(e),this.loadModuleFrameAgnostic("select-pure").then(()=>{this.getList()}),new h("click",t=>{t.preventDefault(),this.write()}).delegateTo(e,r.writeTrigger)}getList(){const e=this.getModalBody();new u(a.getUrl("systemMaintainerGetList")).get({cache:"no-cache"}).then(async t=>{const s=await t.resolve();s.success===!0&&(e.innerHTML=s.html,m.setButtons(s.buttons))},t=>{a.handleAjaxError(t,e)})}write(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.getModuleContent().dataset.systemMaintainerWriteToken,s=this.findInModal(r.selectPureField).values;new u(a.getUrl()).post({install:{users:s,token:t,action:"systemMaintainerWrite"}}).then(async n=>{const o=await n.resolve();o.success===!0?Array.isArray(o.status)&&o.status.forEach(l=>{c.success(l.title,l.message)}):c.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},n=>{a.handleAjaxError(n,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var f=new g;export{f as default};
