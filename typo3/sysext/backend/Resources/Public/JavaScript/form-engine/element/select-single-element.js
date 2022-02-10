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
import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import FormEngine from"@typo3/backend/form-engine.js";class SelectSingleElement{constructor(){this.initialize=(e,t)=>{let n=document.querySelector(e);t=t||{},new RegularEvent("change",e=>{const t=e.target,n=t.parentElement.querySelector(".input-group-icon");null!==n&&(n.innerHTML=t.options[t.selectedIndex].dataset.icon);const c=t.closest(".t3js-formengine-field-item").querySelector(".t3js-forms-select-single-icons");if(null!==c){const e=c.querySelector(".item.active");null!==e&&e.classList.remove("active");const n=c.querySelector('[data-select-index="'+t.selectedIndex+'"]');null!==n&&n.closest(".item").classList.add("active")}}).bindTo(n),t.onChange instanceof Array&&new RegularEvent("change",()=>FormEngine.processOnFieldChange(t.onChange)).bindTo(n),new RegularEvent("click",(e,t)=>{const c=t.closest(".t3js-forms-select-single-icons").querySelector(".item.active");null!==c&&c.classList.remove("active"),n.selectedIndex=parseInt(t.dataset.selectIndex,10),n.dispatchEvent(new Event("change")),t.closest(".item").classList.add("active")}).delegateTo(n.closest(".form-control-wrap"),".t3js-forms-select-single-icons .item:not(.active) a")}}initializeOnReady(e,t){DocumentService.ready().then(()=>{this.initialize(e,t)})}}export default new SelectSingleElement;