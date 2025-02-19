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
import i from"@typo3/core/document-service.js";import n from"@typo3/backend/form-engine-validation.js";import r from"@typo3/core/event/regular-event.js";class l extends HTMLElement{constructor(){super(...arguments),this.element=null}async connectedCallback(){await i.ready(),this.element=document.getElementById(this.getAttribute("recordFieldId")||""),this.element&&(this.registerEventHandler(),import("@typo3/backend/date-time-picker.js").then(({default:e})=>{e.initialize(this.element)}))}registerEventHandler(){new r("formengine.dp.change",e=>{n.validateField(e.target),n.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach(t=>{t.classList.remove("disabled"),t.disabled=!1})}).bindTo(this.element)}}window.customElements.define("typo3-formengine-element-datetime",l);
