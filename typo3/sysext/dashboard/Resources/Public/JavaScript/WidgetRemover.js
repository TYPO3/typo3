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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity"],(function(e,t,a,n,i){"use strict";return new class{constructor(){this.selector=".js-dashboard-remove-widget",a(()=>{this.initialize()})}initialize(){a(document).on("click",this.selector,e=>{e.preventDefault();const t=a(e.currentTarget);n.confirm(t.data("modal-title"),t.data("modal-question"),i.SeverityEnum.warning,[{text:t.data("modal-cancel"),active:!0,btnClass:"btn-default",name:"cancel"},{text:t.data("modal-ok"),btnClass:"btn-warning",name:"delete"}]).on("button.clicked",e=>{"delete"===e.target.getAttribute("name")&&(window.location.href=t.attr("href")),n.dismiss()})})}}}));