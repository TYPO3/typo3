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
import"bootstrap";import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import{topLevelModuleImport}from"@typo3/backend/utility/top-level-module-import.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(t){t.writeTrigger=".t3js-systemMaintainer-write",t.selectPureField=".t3js-systemMaintainer-select-pure"}(Identifiers||(Identifiers={}));class SystemMaintainer extends AbstractInteractableModule{initialize(t){super.initialize(t);window.location!==window.parent.location?topLevelModuleImport("select-pure").then((()=>{this.getList()})):import("select-pure").then((()=>{this.getList()})),new RegularEvent("click",(t=>{t.preventDefault(),this.write()})).delegateTo(t,Identifiers.writeTrigger)}getList(){const t=this.getModalBody();new AjaxRequest(Router.getUrl("systemMaintainerGetList")).get({cache:"no-cache"}).then((async e=>{const o=await e.resolve();!0===o.success&&(t.innerHTML=o.html,Modal.setButtons(o.buttons))}),(e=>{Router.handleAjaxError(e,t)}))}write(){this.setModalButtonsState(!1);const t=this.getModalBody(),e=this.getModuleContent().dataset.systemMaintainerWriteToken,o=this.findInModal(Identifiers.selectPureField).values;new AjaxRequest(Router.getUrl()).post({install:{users:o,token:e,action:"systemMaintainerWrite"}}).then((async t=>{const e=await t.resolve();!0===e.success?Array.isArray(e.status)&&e.status.forEach((t=>{Notification.success(t.title,t.message)})):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(e=>{Router.handleAjaxError(e,t)})).finally((()=>{this.setModalButtonsState(!0)}))}}export default new SystemMaintainer;