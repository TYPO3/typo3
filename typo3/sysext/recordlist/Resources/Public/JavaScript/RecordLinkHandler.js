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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","./LinkBrowser","TYPO3/CMS/Core/Event/RegularEvent"],(function(t,e,i,n){"use strict";n=__importDefault(n);return new class{constructor(){this.currentLink="",this.identifier="",this.currentLink=document.body.dataset.currentLink,this.identifier=document.body.dataset.identifier;const t=document.getElementById("db_list-searchbox-toolbar");t.style.display="block",t.style.position="relative",new n.default("click",(t,e)=>{t.preventDefault();const n=e.closest("span").dataset;i.finalizeFunction(this.identifier+n.uid)}).delegateTo(document,"[data-close]"),new n.default("click",(t,e)=>{t.preventDefault(),i.finalizeFunction(this.currentLink)}).delegateTo(document,"input.t3js-linkCurrent")}}}));