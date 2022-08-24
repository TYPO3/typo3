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
"use strict";var Selectors;!function(e){e.toggleSelector=".t3js-form-field-link-explanation-toggle",e.inputFieldSelector=".t3js-form-field-link-input",e.explanationSelector=".t3js-form-field-link-explanation",e.iconSelector=".t3js-form-field-link-icon"}(Selectors||(Selectors={}));class LinkElement extends HTMLElement{constructor(){super(...arguments),this.element=null,this.container=null,this.toggleSelector=null,this.explanationField=null,this.icon=null}connectedCallback(){this.element=this.querySelector("#"+(this.getAttribute("recordFieldId")||"")),this.element&&(this.container=this.element.closest(".t3js-form-field-link"),this.toggleSelector=this.container.querySelector(Selectors.toggleSelector),this.explanationField=this.container.querySelector(Selectors.explanationSelector),this.icon=this.container.querySelector(Selectors.iconSelector),this.toggleVisibility(""===this.explanationField.value),this.registerEventHandler())}toggleVisibility(e){this.explanationField.classList.toggle("hidden",e),this.element.classList.toggle("hidden",!e)}registerEventHandler(){this.toggleSelector.addEventListener("click",(e=>{e.preventDefault();const t=!this.explanationField.classList.contains("hidden");this.toggleVisibility(t)})),this.container.querySelector(Selectors.inputFieldSelector).addEventListener("change",(()=>{const e=!this.explanationField.classList.contains("hidden");e&&this.toggleVisibility(e),this.disableToggle(),this.clearIcon()}))}disableToggle(){this.toggleSelector.classList.add("disabled"),this.toggleSelector.setAttribute("disabled","disabled")}clearIcon(){this.icon.innerHTML=""}}window.customElements.define("typo3-formengine-element-link",LinkElement);