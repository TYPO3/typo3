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
import s from"@typo3/core/event/regular-event.js";import m from"@typo3/core/document-service.js";import d from"@typo3/backend/form-engine.js";import{selector as f}from"@typo3/core/literals.js";class u{constructor(){this.initialize=(o,t)=>{const i=document.querySelector(o);i!==null&&(t=t||{},new s("change",l=>{const e=l.target,n=e.parentElement.querySelector(".input-group-icon");n!==null&&(n.innerHTML=e.options[e.selectedIndex].dataset.icon);const c=e.closest(".t3js-formengine-field-item").querySelector(".t3js-forms-select-single-icons");if(c!==null){const r=c.querySelector(".form-wizard-icon-list-item button.active, .form-wizard-icon-list-item a.active");r!==null&&r.classList.remove("active");const a=c.querySelector(f`[data-select-index="${e.selectedIndex.toString(10)}"]`);a!==null&&a.closest(".form-wizard-icon-list-item button, .form-wizard-icon-list-item a").classList.add("active")}}).bindTo(i),t.onChange instanceof Array&&new s("change",()=>d.processOnFieldChange(t.onChange)).bindTo(i),new s("click",(l,e)=>{const n=e.closest(".t3js-forms-select-single-icons").querySelector(".form-wizard-icon-list-item button.active, .form-wizard-icon-list-item a.active");n!==null&&n.classList.remove("active"),i.selectedIndex=parseInt(e.dataset.selectIndex,10),i.dispatchEvent(new Event("change")),e.closest(".form-wizard-icon-list-item button, .form-wizard-icon-list-item a").classList.add("active")}).delegateTo(i.closest(".form-control-wrap"),".t3js-forms-select-single-icons .form-wizard-icon-list-item button:not(.active), .t3js-forms-select-single-icons .form-wizard-icon-list-item a:not(.active)"))}}initializeOnReady(o,t){m.ready().then(()=>{this.initialize(o,t)})}}var g=new u;export{g as default};
