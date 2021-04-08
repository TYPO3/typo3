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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./LinkBrowser","TYPO3/CMS/Backend/LegacyTree","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,r,u){"use strict";u=__importDefault(u);return new class{constructor(){r.noop(),new u.default("click",(e,t)=>{e.preventDefault(),n.finalizeFunction(t.getAttribute("href"))}).delegateTo(document,"a.t3js-fileLink"),new u.default("click",(e,t)=>{e.preventDefault(),n.finalizeFunction(document.body.dataset.currentLink)}).delegateTo(document,"input.t3js-linkCurrent")}}}));