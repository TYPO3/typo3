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
import{default as Modal}from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";class WidgetSelector{constructor(){this.selector=".js-dashboard-addWidget",this.modal=null,this.initialize()}initialize(){new RegularEvent("click",((e,t)=>{e.preventDefault();const a=new DocumentFragment;a.append(document.getElementById("widgetSelector").content.cloneNode(!0));const o={type:Modal.types.default,title:t.dataset.modalTitle,size:Modal.sizes.medium,severity:SeverityEnum.notice,content:a,buttons:[{text:TYPO3?.lang?.["button.cancel"]||"Cancel",active:!1,btnClass:"btn-default",name:"cancel"}],additionalCssClasses:["dashboard-modal"],callback:e=>{new RegularEvent("click",(()=>e.hideModal())).delegateTo(e,"a.dashboard-modal-item-block")}},l=Modal.advanced(o);l.addEventListener("button.clicked",(e=>{"cancel"===e.target.getAttribute("name")&&l.hideModal()})),this.modal=l})).delegateTo(document,this.selector),new RegularEvent("typo3.dashboard.addWidgetDone",(()=>{this.modal?.hideModal(),this.modal=null,location.reload()})).bindTo(top.document),document.querySelectorAll(this.selector).forEach((e=>{e.classList.remove("hide")}))}}export default new WidgetSelector;