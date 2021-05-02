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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","bootstrap"],(function(t,e,o,a){"use strict";o=__importDefault(o);return new class{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,o.default(t).each((t,e)=>{const s=new a.Popover(e);o.default(e).data("typo3.bs.popover",s)})}popover(t){t.each((t,e)=>{const s=new a.Popover(e);o.default(e).data("typo3.bs.popover",s)})}setOptions(t,e){const a=(e=e||{}).title||t.data("title")||"",s=e.content||t.data("bs-content")||"";t.attr("data-bs-original-title",a).attr("data-bs-content",s).attr("data-bs-placement","auto"),o.default.each(e,(e,o)=>{this.setOption(t,e,o)})}setOption(t,e,a){"content"===e?(t.attr("data-bs-content",a),t.data("typo3.bs.popover").setContent(a)):t.each((t,s)=>{const p=o.default(s).data("typo3.bs.popover");p&&(p.config[e]=a)})}show(t){t.each((t,e)=>{const a=o.default(e).data("typo3.bs.popover");a&&a.show()})}hide(t){t.each((t,e)=>{const a=o.default(e).data("typo3.bs.popover");a&&a.hide()})}destroy(t){t.each((t,e)=>{const a=o.default(e).data("typo3.bs.popover");a&&a.dispose()})}toggle(t){t.each((t,e)=>{const a=o.default(e).data("typo3.bs.popover");a&&a.toggle()})}}}));