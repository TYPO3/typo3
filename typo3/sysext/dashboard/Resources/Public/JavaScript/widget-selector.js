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
import $ from"jquery";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";class WidgetSelector{constructor(){this.selector=".js-dashboard-addWidget",this.initialize()}initialize(){new RegularEvent("click",(function(e){e.preventDefault();const t={type:Modal.types.default,title:this.dataset.modalTitle,size:Modal.sizes.medium,severity:SeverityEnum.notice,content:$(document.getElementById("widgetSelector").innerHTML),additionalCssClasses:["dashboard-modal"],callback:e=>{e.on("click","a.dashboard-modal-item-block",t=>{e.trigger("modal-dismiss")})}};Modal.advanced(t)})).delegateTo(document,this.selector),document.querySelectorAll(this.selector).forEach(e=>{e.classList.remove("hide")})}}export default new WidgetSelector;