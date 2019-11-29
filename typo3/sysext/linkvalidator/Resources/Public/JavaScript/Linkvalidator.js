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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification"],(function(t,e,i,n){"use strict";return new class{constructor(){this.initializeEvents()}toggleActionButton(t){let e=!0;i("."+t).each((t,n)=>{i(n).prop("checked")&&(e=!1)}),"check"===t?i("#updateLinkList").prop("disabled",e):i("#refreshLinkList").prop("disabled",e)}initializeEvents(){i(".refresh").on("click",()=>{this.toggleActionButton("refresh")}),i(".check").on("click",()=>{this.toggleActionButton("check")}),i(".t3js-update-button").on("click",t=>{const e=i(t.currentTarget),c=e.attr("name");let s="Event triggered";"refreshLinkList"!==c&&"updateLinkList"!==c||(s=e.data("notification-message")),n.success(s)})}}}));