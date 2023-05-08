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
import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";class Features extends AbstractInteractableModule{constructor(){super(...arguments),this.selectorSaveTrigger=".t3js-features-save"}initialize(e){this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,(e=>{e.preventDefault(),this.save()}))}getContent(){const e=this.getModalBody();new AjaxRequest(Router.getUrl("featuresGetContent")).get({cache:"no-cache"}).then((async t=>{const s=await t.resolve();!0===s.success&&"undefined"!==s.html&&s.html.length>0?(e.empty().append(s.html),Modal.setButtons(s.buttons)):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)}))}save(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.getModuleContent().data("features-save-token"),s={};for(const e of this.findInModal("form").serializeArray())s[e.name]=e.value;s["install[action]"]="featuresSave",s["install[token]"]=t,new AjaxRequest(Router.getUrl()).post(s).then((async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?(t.status.forEach((e=>{Notification.showMessage(e.title,e.message,e.severity)})),this.getContent()):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)})).finally((()=>{this.setModalButtonsState(!0)}))}}export default new Features;