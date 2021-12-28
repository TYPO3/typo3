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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","bootstrap"],(function(t,e,o,a){"use strict";o=__importDefault(o);return new class{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,(0,o.default)(t).each((t,e)=>{const p=new a.Popover(e);(0,o.default)(e).data("typo3.bs.popover",p)})}popover(t){t.each((t,e)=>{const p=new a.Popover(e);(0,o.default)(e).data("typo3.bs.popover",p)})}setOptions(t,e){(e=e||{}).html=!0;const a=e.title||t.data("title")||"",p=e.content||t.data("bs-content")||"";t.attr("data-bs-original-title",a).attr("data-bs-content",p).attr("data-bs-placement","auto"),o.default.each(e,(e,o)=>{this.setOption(t,e,o)})}setOption(t,e,a){if("content"===e){const e=t.data("typo3.bs.popover");e._config.content=a,e.setContent(e.tip)}else t.each((t,p)=>{const s=(0,o.default)(p).data("typo3.bs.popover");s&&(s._config[e]=a)})}show(t){t.each((t,e)=>{const a=(0,o.default)(e).data("typo3.bs.popover");a&&a.show()})}hide(t){t.each((t,e)=>{const a=(0,o.default)(e).data("typo3.bs.popover");a&&a.hide()})}destroy(t){t.each((t,e)=>{const a=(0,o.default)(e).data("typo3.bs.popover");a&&a.dispose()})}toggle(t){t.each((t,e)=>{const a=(0,o.default)(e).data("typo3.bs.popover");a&&a.toggle()})}update(t){t.data("typo3.bs.popover")._popper.update()}}}));