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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Backend/Modal"],(function(t,e,r,i){"use strict";r=__importDefault(r);return new class{constructor(){(0,r.default)(()=>{(0,r.default)(document).on("click",".t3js-confirm-trigger",t=>{const e=(0,r.default)(t.currentTarget);i.confirm(e.data("title"),e.data("message")).on("confirm.button.ok",()=>{(0,r.default)("#t3js-submit-field").attr("name",e.attr("name")).closest("form").trigger("submit"),i.currentModal.trigger("modal-dismiss")}).on("confirm.button.cancel",()=>{i.currentModal.trigger("modal-dismiss")})}),(0,r.default)(".t3js-impexp-toggledisabled").on("click",()=>{const t=(0,r.default)('table.t3js-impexp-preview tr[data-active="hidden"] input.t3js-exclude-checkbox');if(t.length){const e=t.get(0);t.prop("checked",!e.checked)}})})}}}));