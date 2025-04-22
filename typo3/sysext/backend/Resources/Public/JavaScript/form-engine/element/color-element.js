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
import i from"@typo3/core/document-service.js";import n from"@typo3/core/event/regular-event.js";import r from"@typo3/backend/form-engine-validation.js";import{selector as l}from"@typo3/core/literals.js";import"@typo3/backend/color-picker.js";class o extends HTMLElement{constructor(){super(...arguments),this.element=null}async connectedCallback(){if(this.element!==null)return;const e=this.getAttribute("recordFieldId");e!==null&&(await i.ready(),this.element=this.querySelector(l`#${e}`),this.element&&this.registerEventHandler())}registerEventHandler(){const e=document.querySelector(l`input[name="${this.element.dataset.formengineInputName}"]`);new n("blur",t=>{e.value=t.target.value,this.handleEvent(t)}).bindTo(this.element),new n("formengine.cp.change",t=>{this.handleEvent(t)}).bindTo(this.element)}handleEvent(e){r.validateField(e.target),r.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach(t=>{t.classList.remove("disabled"),t.disabled=!1})}}window.customElements.define("typo3-formengine-element-color",o);
