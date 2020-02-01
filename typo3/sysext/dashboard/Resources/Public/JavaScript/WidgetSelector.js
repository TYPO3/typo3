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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity"],(function(e,t,i,a,s){"use strict";return new class{constructor(){this.selector=".js-dashboard-addWidget",i(()=>{this.initialize()})}initialize(){i(document).on("click",this.selector,e=>{e.preventDefault();const t=i(e.currentTarget),d={type:a.types.default,title:t.data("modal-title"),size:a.sizes.medium,severity:s.SeverityEnum.notice,content:i(i("#widgetSelector").html()),additionalCssClasses:["dashboard-modal"],callback:e=>{e.on("click","a.dashboard-modal-item-block",t=>{e.trigger("modal-dismiss")})}};a.advanced(d)})}}}));