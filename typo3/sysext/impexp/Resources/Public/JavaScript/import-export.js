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
import r from"@typo3/backend/modal.js";import o from"@typo3/core/event/regular-event.js";import n from"@typo3/core/document-service.js";class s{constructor(){n.ready().then(()=>this.registerEvents())}registerEvents(){new o("click",this.triggerConfirmation).delegateTo(document,".t3js-confirm-trigger");const e=document.querySelector(".t3js-impexp-toggledisabled");e!==null&&new o("click",this.toggleDisabled).bindTo(e)}triggerConfirmation(){const e=r.confirm(this.dataset.title,this.dataset.message);e.addEventListener("confirm.button.ok",()=>{const t=document.getElementById("t3js-submit-field");t.name=this.name,t.closest("form").submit(),e.hideModal()}),e.addEventListener("confirm.button.cancel",()=>{e.hideModal()})}toggleDisabled(){const e=document.querySelectorAll('table.t3js-impexp-preview tr[data-active="hidden"] input.t3js-exclude-checkbox');if(e.length>0){const t=e.item(0);e.forEach(i=>{i.checked=!t.checked})}}}var c=new s;export{c as default};
