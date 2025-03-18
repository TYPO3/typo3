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
import DocumentService from"@typo3/core/document-service.js";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import RegularEvent from"@typo3/core/event/regular-event.js";class DatetimeElement extends HTMLElement{constructor(){super(...arguments),this.element=null}async connectedCallback(){await DocumentService.ready(),this.element=document.getElementById(this.getAttribute("recordFieldId")||""),this.element&&(this.registerEventHandler(),import("@typo3/backend/date-time-picker.js").then((({default:e})=>{e.initialize(this.element)})))}registerEventHandler(){new RegularEvent("formengine.dp.change",(e=>{FormEngineValidation.validateField(e.target),FormEngineValidation.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach((e=>{e.classList.remove("disabled"),e.disabled=!1}))})).bindTo(this.element)}}window.customElements.define("typo3-formengine-element-datetime",DatetimeElement);