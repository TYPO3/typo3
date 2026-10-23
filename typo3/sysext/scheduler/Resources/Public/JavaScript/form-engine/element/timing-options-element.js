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
import s from"@typo3/backend/form-engine.js";import{selector as a}from"@typo3/core/literals.js";import g from"@typo3/core/event/regular-event.js";var i;(function(n){n.single="1",n.recurring="2"})(i||(i={}));class u extends HTMLElement{async connectedCallback(){await s.ready(),this.fieldPrefix=this.getAttribute("fieldPrefix")??"",this.registerEventHandler()}registerEventHandler(){new g("change",()=>{this.toggleRunningType()}).delegateTo(this,a`input[name='${this.fieldPrefix}[runningType]']`),this.toggleRunningType()}toggleRunningType(){const o=this.querySelector(a`input[name='${this.fieldPrefix}[runningType]']:checked`).value;this.querySelectorAll(".t3js-timing-options-end, .t3js-timing-options-parallel, .t3js-timing-options-frequency").forEach(t=>{if(t.style.display=o===i.recurring?"block":"none",t.classList.contains("t3js-timing-options-frequency")){const e=t.querySelector("input[data-formengine-validation-rules]");if(e===null)return;let r,l;o===i.recurring?(r=e.getAttribute("data-formengine-validation-rules")==="[]",l='[{"type":"required"}]'):(r=e.getAttribute("data-formengine-validation-rules")!=="[]",l="[]"),r&&(e.setAttribute("data-formengine-validation-rules",l),TYPO3.FormEngine.Validation.initializeInputField(e.dataset.formengineInputName??""),TYPO3.FormEngine.Validation.validate())}})}}window.customElements.define("typo3-formengine-element-timing-options",u);
