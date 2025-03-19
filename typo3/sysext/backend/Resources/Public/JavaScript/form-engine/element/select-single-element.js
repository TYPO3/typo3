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
import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import FormEngine from"@typo3/backend/form-engine.js";import{selector}from"@typo3/core/literals.js";class SelectSingleElement{constructor(){this.initialize=(e,t)=>{const i=document.querySelector(e);null!==i&&(t=t||{},new RegularEvent("change",(e=>{const t=e.target,i=t.parentElement.querySelector(".input-group-icon");null!==i&&(i.innerHTML=t.options[t.selectedIndex].dataset.icon);const n=t.closest(".t3js-formengine-field-item").querySelector(".t3js-forms-select-single-icons");if(null!==n){const e=n.querySelector(".form-wizard-icon-list-item button.active, .form-wizard-icon-list-item a.active");null!==e&&e.classList.remove("active");const i=n.querySelector(selector`[data-select-index="${t.selectedIndex.toString(10)}"]`);null!==i&&i.closest(".form-wizard-icon-list-item button, .form-wizard-icon-list-item a").classList.add("active")}})).bindTo(i),t.onChange instanceof Array&&new RegularEvent("change",(()=>FormEngine.processOnFieldChange(t.onChange))).bindTo(i),new RegularEvent("click",((e,t)=>{const n=t.closest(".t3js-forms-select-single-icons").querySelector(".form-wizard-icon-list-item button.active, .form-wizard-icon-list-item a.active");null!==n&&n.classList.remove("active"),i.selectedIndex=parseInt(t.dataset.selectIndex,10),i.dispatchEvent(new Event("change")),t.closest(".form-wizard-icon-list-item button, .form-wizard-icon-list-item a").classList.add("active")})).delegateTo(i.closest(".form-control-wrap"),".t3js-forms-select-single-icons .form-wizard-icon-list-item button:not(.active), .t3js-forms-select-single-icons .form-wizard-icon-list-item a:not(.active)"))}}initializeOnReady(e,t){DocumentService.ready().then((()=>{this.initialize(e,t)}))}}export default new SelectSingleElement;