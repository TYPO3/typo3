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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Backend/Notification"],(function(t,e,i,n){"use strict";i=__importDefault(i);return new class{constructor(){this.initializeEvents()}toggleActionButton(t){let e=!0;i.default("."+t).each((t,n)=>{i.default(n).prop("checked")&&(e=!1)}),"check"===t?i.default("#updateLinkList").prop("disabled",e):i.default("#refreshLinkList").prop("disabled",e)}initializeEvents(){i.default(".refresh").on("click",()=>{this.toggleActionButton("refresh")}),i.default(".check").on("click",()=>{this.toggleActionButton("check")}),i.default(".t3js-update-button").on("click",t=>{const e=i.default(t.currentTarget),r=e.attr("name");let a="Event triggered";"refreshLinkList"!==r&&"updateLinkList"!==r||(a=e.data("notification-message")),n.success(a)})}}}));