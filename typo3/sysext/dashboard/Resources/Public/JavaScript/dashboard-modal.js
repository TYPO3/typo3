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
import $ from"jquery";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import RegularEvent from"@typo3/core/event/regular-event.js";class DashboardModal{constructor(){this.selector=".js-dashboard-modal",this.initialize()}initialize(){new RegularEvent("click",(function(t){t.preventDefault();const e={type:Modal.types.default,title:this.dataset.modalTitle,size:Modal.sizes.medium,severity:SeverityEnum.notice,content:$(document.getElementById("dashboardModal-"+this.dataset.modalIdentifier).innerHTML),additionalCssClasses:["dashboard-modal"],callback:t=>{t.on("submit",".dashboardModal-form",e=>{t.trigger("modal-dismiss")}),t.on("button.clicked",e=>{if("save"===e.target.getAttribute("name")){t.find("form").trigger("submit")}else t.trigger("modal-dismiss")})},buttons:[{text:this.dataset.buttonCloseText,btnClass:"btn-default",name:"cancel"},{text:this.dataset.buttonOkText,active:!0,btnClass:"btn-warning",name:"save"}]};Modal.advanced(e)})).delegateTo(document,this.selector)}}export default new DashboardModal;