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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./LinkBrowser"],(function(t,e,i,n){"use strict";i=__importDefault(i);return new class{constructor(){this.currentLink="",this.linkPage=t=>{t.preventDefault(),n.finalizeFunction(i.default(t.currentTarget).attr("href"))},this.linkPageByTextfield=t=>{t.preventDefault();let e=i.default("#luid").val();if(!e)return;const r=parseInt(e,10);isNaN(r)||(e="t3://page?uid="+r),n.finalizeFunction(e)},this.linkCurrent=t=>{t.preventDefault(),n.finalizeFunction(this.currentLink)},i.default(()=>{this.currentLink=i.default("body").data("currentLink"),i.default("a.t3js-pageLink").on("click",this.linkPage),i.default("input.t3js-linkCurrent").on("click",this.linkCurrent),i.default("input.t3js-pageLink").on("click",this.linkPageByTextfield)})}}}));