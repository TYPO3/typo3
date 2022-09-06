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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","bootstrap"],(function(t,e,a,o){"use strict";a=__importDefault(a);return new class{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,(0,a.default)(t).each((t,e)=>{this.applyTitleIfAvailable(e);const p=new o.Popover(e);(0,a.default)(e).data("typo3.bs.popover",p)})}popover(t){t.each((t,e)=>{this.applyTitleIfAvailable(e);const p=new o.Popover(e);(0,a.default)(e).data("typo3.bs.popover",p)})}setOptions(t,e){(e=e||{}).html=!0;const o=e.title||t.data("title")||t.data("bs-title")||"",p=e.content||t.data("bs-content")||"";t.attr("data-bs-original-title",o).attr("data-bs-content",p).attr("data-bs-placement","auto"),delete e.title,delete e.content,a.default.each(e,(e,a)=>{this.setOption(t,e,a)});const s=t.data("typo3.bs.popover");s&&s.setContent({".popover-header":o,".popover-body":p})}setOption(t,e,o){t.each((t,p)=>{const s=(0,a.default)(p).data("typo3.bs.popover");s&&(s._config[e]=o)})}show(t){t.each((t,e)=>{const o=(0,a.default)(e).data("typo3.bs.popover");o&&o.show()})}hide(t){t.each((t,e)=>{const o=(0,a.default)(e).data("typo3.bs.popover");o&&o.hide()})}destroy(t){t.each((t,e)=>{const o=(0,a.default)(e).data("typo3.bs.popover");o&&o.dispose()})}toggle(t){t.each((t,e)=>{const o=(0,a.default)(e).data("typo3.bs.popover");o&&o.toggle()})}update(t){t.data("typo3.bs.popover")._popper.update()}applyTitleIfAvailable(t){const e=t.title||t.dataset.title||"";e&&(t.dataset.bsTitle=e)}}}));