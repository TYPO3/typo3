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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,a){"use strict";a=__importDefault(a);return new class{constructor(){this.registerEvents()}registerEvents(){(0,a.default)('input[type="checkbox"][data-lang]').on("change",this.toggleNewButton)}toggleNewButton(t){const e=(0,a.default)(t.currentTarget),n=parseInt(e.data("lang"),10),r=(0,a.default)(".t3js-language-new-"+n),s=(0,a.default)('input[type="checkbox"][data-lang="'+n+'"]:checked'),u=[];s.each((t,e)=>{u.push("cmd[pages]["+e.dataset.uid+"][localize]="+n)});const l=r.data("editUrl")+"&"+u.join("&");r.attr("href",l),r.toggleClass("disabled",0===s.length)}}}));