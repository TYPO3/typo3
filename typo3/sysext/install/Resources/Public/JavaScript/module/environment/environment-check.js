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
import"bootstrap";import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{InfoBox}from"@typo3/install/renderable/info-box.js";import Router from"@typo3/install/router.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.executeTrigger=".t3js-environmentCheck-execute",e.outputContainer=".t3js-environmentCheck-output"}(Identifiers||(Identifiers={}));class EnvironmentCheck extends AbstractInteractableModule{initialize(e){super.initialize(e),this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js").then((()=>{this.runTests()})),new RegularEvent("click",(e=>{e.preventDefault(),this.runTests()})).delegateTo(e,Identifiers.executeTrigger)}runTests(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=e.querySelector(Identifiers.outputContainer);null!==t&&this.renderProgressBar(t),new AjaxRequest(Router.getUrl("environmentCheckGetStatus")).get({cache:"no-cache"}).then((async t=>{const o=await t.resolve();if(e.innerHTML=o.html,Modal.setButtons(o.buttons),!0===o.success&&"object"==typeof o.status)for(const t of Object.values(o.status))for(const o of t)e.querySelector(Identifiers.outputContainer).append(InfoBox.create(o.severity,o.title,o.message));else Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)})).finally((()=>{this.setModalButtonsState(!0)}))}}export default new EnvironmentCheck;