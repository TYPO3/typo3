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
import"bootstrap";import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.writeTrigger=".t3js-systemMaintainer-write",e.selectPureField=".t3js-systemMaintainer-select-pure"}(Identifiers||(Identifiers={}));class SystemMaintainer extends AbstractInteractableModule{initialize(e){super.initialize(e),this.loadModuleFrameAgnostic("select-pure").then((()=>{this.getList()})),new RegularEvent("click",(e=>{e.preventDefault(),this.write()})).delegateTo(e,Identifiers.writeTrigger)}getList(){const e=this.getModalBody();new AjaxRequest(Router.getUrl("systemMaintainerGetList")).get({cache:"no-cache"}).then((async t=>{const s=await t.resolve();!0===s.success&&(e.innerHTML=s.html,Modal.setButtons(s.buttons))}),(t=>{Router.handleAjaxError(t,e)}))}write(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.getModuleContent().dataset.systemMaintainerWriteToken,s=this.findInModal(Identifiers.selectPureField).values;new AjaxRequest(Router.getUrl()).post({install:{users:s,token:t,action:"systemMaintainerWrite"}}).then((async e=>{const t=await e.resolve();!0===t.success?Array.isArray(t.status)&&t.status.forEach((e=>{Notification.success(e.title,e.message)})):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)})).finally((()=>{this.setModalButtonsState(!0)}))}}export default new SystemMaintainer;