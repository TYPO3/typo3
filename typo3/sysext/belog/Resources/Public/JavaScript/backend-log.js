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
import Modal from"@typo3/backend/modal.js";import DocumentService from"@typo3/core/document-service.js";import DateTimePicker from"@typo3/backend/date-time-picker.js";import"@typo3/backend/input/clearable.js";import{MessageUtility}from"@typo3/backend/utility/message-utility.js";class BackendLog{constructor(){this.clearableElements=null,this.dateTimePickerElements=null,this.elementBrowserElements=null,DocumentService.ready().then((()=>{this.clearableElements=document.querySelectorAll(".t3js-clearable"),this.dateTimePickerElements=document.querySelectorAll(".t3js-datetimepicker"),this.elementBrowserElements=document.querySelectorAll(".t3js-element-browser"),this.initializeClearableElements(),this.initializeDateTimePickerElements(),this.initializeElementBrowserElements(),this.initializeElementBrowserEventListener()}))}initializeClearableElements(){this.clearableElements.forEach((e=>e.clearable()))}initializeDateTimePickerElements(){this.dateTimePickerElements.forEach((e=>DateTimePicker.initialize(e)))}initializeElementBrowserElements(){this.elementBrowserElements.forEach((e=>{const t=document.getElementById(e.dataset.triggerFor);e.dataset.params=t.name+"|||pages",e.addEventListener("click",(e=>{e.preventDefault();const t=e.currentTarget;Modal.advanced({type:Modal.types.iframe,content:t.dataset.target+"&mode="+t.dataset.mode+"&bparams="+t.dataset.params,size:Modal.sizes.large})}))}))}initializeElementBrowserEventListener(){window.addEventListener("message",(e=>{if(!MessageUtility.verifyOrigin(e.origin)||"typo3:elementBrowser:elementAdded"!==e.data.actionName||"string"!=typeof e.data.fieldName||"string"!=typeof e.data.value)return;const t=document.querySelector('input[name="'+e.data.fieldName+'"]');t&&(t.value=e.data.value.split("_").pop())}))}}export default new BackendLog;