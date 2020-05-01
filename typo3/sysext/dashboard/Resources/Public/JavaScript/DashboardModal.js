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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Core/Event/RegularEvent"],(function(t,e,a,s,i,n){"use strict";return new class{constructor(){this.selector=".js-dashboard-modal",this.initialize()}initialize(){new n("click",(function(t){t.preventDefault();const e={type:s.types.default,title:this.dataset.modalTitle,size:s.sizes.medium,severity:i.SeverityEnum.notice,content:a(document.getElementById(`dashboardModal-${this.dataset.modalIdentifier}`).innerHTML),additionalCssClasses:["dashboard-modal"],callback:t=>{t.on("submit",".dashboardModal-form",e=>{t.trigger("modal-dismiss")}),t.on("button.clicked",e=>{if("save"===e.target.getAttribute("name")){t.find("form").submit()}else t.trigger("modal-dismiss")})},buttons:[{text:this.dataset.buttonCloseText,btnClass:"btn-default",name:"cancel"},{text:this.dataset.buttonOkText,active:!0,btnClass:"btn-warning",name:"save"}]};s.advanced(e)})).delegateTo(document,this.selector)}}}));