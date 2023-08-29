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
import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.saveTrigger=".t3js-features-save"}(Identifiers||(Identifiers={}));class Features extends AbstractInteractableModule{initialize(e){super.initialize(e),this.getContent(),new RegularEvent("click",(e=>{e.preventDefault(),this.save()})).delegateTo(e,Identifiers.saveTrigger)}getContent(){const e=this.getModalBody();new AjaxRequest(Router.getUrl("featuresGetContent")).get({cache:"no-cache"}).then((async t=>{const o=await t.resolve();!0===o.success&&"undefined"!==o.html&&o.html.length>0?(e.innerHTML=o.html,Modal.setButtons(o.buttons)):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)}))}save(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.getModuleContent().dataset.featuresSaveToken,o={},s=new FormData(this.findInModal("form"));for(const[e,t]of s)o[e]=t.toString();o["install[action]"]="featuresSave",o["install[token]"]=t,new AjaxRequest(Router.getUrl()).post(o).then((async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?(t.status.forEach((e=>{Notification.showMessage(e.title,e.message,e.severity)})),this.getContent()):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)})).finally((()=>{this.setModalButtonsState(!0)}))}}export default new Features;