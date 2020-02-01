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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity"],(function(t,e,a,i,s){"use strict";return new class{constructor(){this.selector=".js-dashboard-modal",a(()=>{this.initialize()})}initialize(){a(document).on("click",this.selector,t=>{t.preventDefault();const e=a(t.currentTarget),n={type:i.types.default,title:e.data("modal-title"),size:i.sizes.medium,severity:s.SeverityEnum.notice,content:a(a("#dashboardModal-"+e.data("modal-identifier")).html()),additionalCssClasses:["dashboard-modal"],callback:t=>{t.on("submit",".dashboardModal-form",e=>{t.trigger("modal-dismiss")}),t.on("button.clicked",e=>{if("save"===e.target.getAttribute("name")){const e=t.find("form");a('<input type="submit">').hide().appendTo(e).click().remove()}else t.trigger("modal-dismiss")})},buttons:[{text:e.data("button-close-text"),btnClass:"btn-default",name:"cancel"},{text:e.data("button-ok-text"),active:!0,btnClass:"btn-warning",name:"save"}]};i.advanced(n)})}}}));