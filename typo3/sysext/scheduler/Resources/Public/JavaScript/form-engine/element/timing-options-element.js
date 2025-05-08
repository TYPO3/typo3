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
import s from"@typo3/backend/form-engine.js";import{selector as a}from"@typo3/core/literals.js";import g from"@typo3/core/event/regular-event.js";var i;(function(t){t.single="1",t.recurring="2"})(i||(i={}));class u extends HTMLElement{async connectedCallback(){await s.ready(),this.fieldPrefix=this.getAttribute("fieldPrefix"),this.registerEventHandler()}registerEventHandler(){new g("change",()=>{this.toggleRunningType()}).delegateTo(this,a`input[name='${this.fieldPrefix}[runningType]']`),this.toggleRunningType()}toggleRunningType(){const l=this.querySelector(a`input[name='${this.fieldPrefix}[runningType]']:checked`).value;this.querySelectorAll(".t3js-timing-options-end, .t3js-timing-options-parallel, .t3js-timing-options-frequency").forEach(n=>{if(n.style.display=l===i.recurring?"block":"none",n.classList.contains("t3js-timing-options-frequency")){const e=n.querySelector("input[data-formengine-validation-rules]");let r,o;l===i.recurring?(r=e.getAttribute("data-formengine-validation-rules")==="[]",o='[{"type":"required"}]'):(r=e.getAttribute("data-formengine-validation-rules")!=="[]",o="[]"),r&&(e.setAttribute("data-formengine-validation-rules",o),TYPO3.FormEngine.Validation.initializeInputField(e.dataset.formengineInputName),TYPO3.FormEngine.Validation.validate())}})}}window.customElements.define("typo3-formengine-element-timing-options",u);
