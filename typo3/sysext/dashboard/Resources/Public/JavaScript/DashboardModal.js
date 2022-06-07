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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Core/Event/RegularEvent"],(function(t,e,a,i,s,n){"use strict";a=__importDefault(a);return new class{constructor(){this.selector=".js-dashboard-modal",this.initialize()}initialize(){new n("click",(function(t){t.preventDefault();const e={type:i.types.default,title:this.dataset.modalTitle,size:i.sizes.medium,severity:s.SeverityEnum.notice,content:(0,a.default)(document.getElementById("dashboardModal-"+this.dataset.modalIdentifier).innerHTML),additionalCssClasses:["dashboard-modal"],callback:t=>{t.on("submit",".dashboardModal-form",e=>{t.trigger("modal-dismiss")}),t.on("button.clicked",e=>{if("save"===e.target.getAttribute("name")){t.find("form").trigger("submit")}else t.trigger("modal-dismiss")})},buttons:[{text:this.dataset.buttonCloseText,btnClass:"btn-default",name:"cancel"},{text:this.dataset.buttonOkText,active:!0,btnClass:"btn-warning",name:"save"}]};i.advanced(e)})).delegateTo(document,this.selector)}}}));