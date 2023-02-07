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
import{AbstractSortableSelectItems}from"@typo3/backend/form-engine/element/abstract-sortable-select-items.js";import DocumentService from"@typo3/core/document-service.js";import FormEngine from"@typo3/backend/form-engine.js";import SelectBoxFilter from"@typo3/backend/form-engine/element/extra/select-box-filter.js";import RegularEvent from"@typo3/core/event/regular-event.js";class SelectMultipleSideBySideElement extends AbstractSortableSelectItems{constructor(e,t){super(),this.selectedOptionsElement=null,this.availableOptionsElement=null,DocumentService.ready().then((n=>{this.selectedOptionsElement=n.getElementById(e),this.availableOptionsElement=n.getElementById(t),this.registerEventHandler()}))}registerEventHandler(){this.registerSortableEventHandler(this.selectedOptionsElement),this.registerKeyboardEvents(),this.availableOptionsElement.addEventListener("click",(e=>{const t=e.currentTarget;this.handleOptionChecked(t)})),new SelectBoxFilter(this.availableOptionsElement)}handleOptionChecked(e){const t=e.dataset.relatedfieldname;if(t){const n=e.dataset.exclusivevalues,l=e.querySelectorAll("option:checked");l.length>0&&l.forEach((e=>{FormEngine.setSelectOptionFromExternalSource(t,e.value,e.textContent,e.getAttribute("title"),n,e)}))}}registerKeyboardEvents(){new RegularEvent("keydown",(e=>{const t=e.currentTarget;"Enter"===e.code&&(e.preventDefault(),this.handleOptionChecked(t))})).bindTo(this.availableOptionsElement)}}export default SelectMultipleSideBySideElement;