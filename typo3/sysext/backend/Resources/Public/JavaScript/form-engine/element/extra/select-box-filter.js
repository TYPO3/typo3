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
import s from"@typo3/core/event/regular-event.js";var l;(function(i){i.fieldContainerSelector=".t3js-formengine-field-group",i.filterTextFieldSelector=".t3js-formengine-multiselect-filter-textfield",i.filterSelectFieldSelector=".t3js-formengine-multiselect-filter-dropdown"})(l||(l={}));class r{constructor(t){this.selectElement=null,this.availableOptions=null,this.selectElement=t,this.initializeEvents()}static toggleOptGroup(t){const e=t.parentElement;e instanceof HTMLOptGroupElement&&(e.querySelectorAll("option:not([hidden]):not([disabled]):not(.hidden)").length===0?e.hidden=!0:(e.hidden=!1,e.disabled=!1,e.classList.remove("hidden")))}initializeEvents(){const t=this.selectElement.closest(".form-wizards-wrap");t!==null&&(new s("input",e=>{this.filter(e.target.value)}).delegateTo(t,l.filterTextFieldSelector),new s("change",e=>{this.filter(e.target.value)}).delegateTo(t,l.filterSelectFieldSelector))}filter(t){this.availableOptions===null&&(this.availableOptions=this.selectElement.querySelectorAll("option"));const e=new RegExp(t,"i");this.availableOptions.forEach(n=>{n.hidden=t.length>0&&n.textContent.match(e)===null,r.toggleOptGroup(n)})}}export{r as default};
