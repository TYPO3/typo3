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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./LinkBrowser","TYPO3/CMS/Backend/LegacyTree"],(function(t,e,n,i,r){"use strict";n=__importDefault(n);return new class{constructor(){this.currentLink="",this.linkFile=t=>{t.preventDefault(),i.finalizeFunction(n.default(t.currentTarget).attr("href"))},this.linkCurrent=t=>{t.preventDefault(),i.finalizeFunction(this.currentLink)},r.noop(),n.default(()=>{this.currentLink=n.default("body").data("currentLink"),n.default("a.t3js-fileLink").on("click",this.linkFile),n.default("input.t3js-linkCurrent").on("click",this.linkCurrent)})}}}));