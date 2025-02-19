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
import{AbstractSortableSelectItems as r}from"@typo3/backend/form-engine/element/abstract-sortable-select-items.js";import s from"@typo3/core/document-service.js";import a from"@typo3/backend/form-engine.js";import o from"@typo3/backend/form-engine/element/extra/select-box-filter.js";import c from"@typo3/core/event/regular-event.js";import d from"@typo3/backend/utility.js";class m extends r{constructor(e,t){super(),this.selectedOptionsElement=null,this.availableOptionsElement=null,s.ready().then(l=>{this.selectedOptionsElement=l.getElementById(e),this.availableOptionsElement=l.getElementById(t),!(this.selectedOptionsElement===null||this.availableOptionsElement===null)&&this.registerEventHandler()})}registerEventHandler(){this.registerSortableEventHandler(this.selectedOptionsElement),this.registerKeyboardEvents(),this.availableOptionsElement.addEventListener("click",e=>{const t=e.currentTarget;this.handleOptionChecked(t)}),new o(this.availableOptionsElement)}handleOptionChecked(e){const t=e.dataset.relatedfieldname;if(t){const l=d.trimExplode(",",e.dataset?.exclusivevalues??""),n=e.querySelectorAll("option:checked");n.length>0&&n.forEach(i=>{a.setSelectOptionFromExternalSource(t,i.value,i.textContent,i.getAttribute("title"),l,i)})}}registerKeyboardEvents(){new c("keydown",e=>{const t=e.currentTarget;e.code==="Enter"&&(e.preventDefault(),this.handleOptionChecked(t))}).bindTo(this.availableOptionsElement)}}export{m as default};
