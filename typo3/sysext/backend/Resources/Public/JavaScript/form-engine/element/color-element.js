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
import RegularEvent from"@typo3/core/event/regular-event.js";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import{selector}from"@typo3/core/literals.js";class ColorElement extends HTMLElement{constructor(){super(...arguments),this.element=null}connectedCallback(){const e=this.getAttribute("recordFieldId");if(null===e)return;if(this.element=this.querySelector(selector`#${e}`),!this.element)return;this.registerEventHandler();const t=this.hasAttribute("colorPalette")?this.getAttribute("colorPalette").split(";"):[],n=this.hasAttribute("opacity");import("@typo3/backend/color-picker.js").then((({default:e})=>{e.initialize(this.element,{swatches:t,opacity:n})}))}registerEventHandler(){const e=document.querySelector(selector`input[name="${this.element.dataset.formengineInputName}"]`);new RegularEvent("blur",(t=>{e.value=t.target.value})).bindTo(this.element),new RegularEvent("formengine.cp.change",(e=>{FormEngineValidation.validateField(e.target),FormEngineValidation.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach((e=>{e.classList.remove("disabled"),e.disabled=!1}))})).bindTo(this.element)}}window.customElements.define("typo3-formengine-element-color",ColorElement);