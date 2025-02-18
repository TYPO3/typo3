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
import o from"@typo3/core/event/regular-event.js";import m from"@typo3/core/document-service.js";import d from"@typo3/backend/form-engine.js";import{selector as f}from"@typo3/core/literals.js";class u{constructor(){this.initialize=(c,t)=>{const n=document.querySelector(c);n!==null&&(t=t||{},new o("change",l=>{const e=l.target,i=e.parentElement.querySelector(".input-group-icon");i!==null&&(i.innerHTML=e.options[e.selectedIndex].dataset.icon);const s=e.closest(".t3js-formengine-field-item").querySelector(".t3js-forms-select-single-icons");if(s!==null){const r=s.querySelector(".form-wizard-icon-list-item a.active");r!==null&&r.classList.remove("active");const a=s.querySelector(f`[data-select-index="${e.selectedIndex.toString(10)}"]`);a!==null&&a.closest(".form-wizard-icon-list-item a").classList.add("active")}}).bindTo(n),t.onChange instanceof Array&&new o("change",()=>d.processOnFieldChange(t.onChange)).bindTo(n),new o("click",(l,e)=>{const i=e.closest(".t3js-forms-select-single-icons").querySelector(".form-wizard-icon-list-item a.active");i!==null&&i.classList.remove("active"),n.selectedIndex=parseInt(e.dataset.selectIndex,10),n.dispatchEvent(new Event("change")),e.closest(".form-wizard-icon-list-item a").classList.add("active")}).delegateTo(n.closest(".form-control-wrap"),".t3js-forms-select-single-icons .form-wizard-icon-list-item a:not(.active)"))}}initializeOnReady(c,t){m.ready().then(()=>{this.initialize(c,t)})}}var g=new u;export{g as default};
