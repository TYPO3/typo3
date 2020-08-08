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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./LinkBrowser"],(function(t,e,i,n){"use strict";i=__importDefault(i);return new class{constructor(){this.currentLink="",this.identifier="",this.linkRecord=t=>{t.preventDefault();const e=i.default(t.currentTarget).parents("span").data();n.finalizeFunction(this.identifier+e.uid)},this.linkCurrent=t=>{t.preventDefault(),n.finalizeFunction(this.currentLink)},i.default(()=>{const t=i.default("body");this.currentLink=t.data("currentLink"),this.identifier=t.data("identifier");const e=document.getElementById("db_list-searchbox-toolbar");e.style.display="block",e.style.position="relative",i.default("[data-close]").on("click",this.linkRecord),i.default("input.t3js-linkCurrent").on("click",this.linkCurrent)})}}}));