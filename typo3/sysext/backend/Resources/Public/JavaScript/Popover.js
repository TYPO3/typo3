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
import $ from"jquery";import{Popover as BootstrapPopover}from"bootstrap";class Popover{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,$(t).each((t,o)=>{const e=new BootstrapPopover(o);$(o).data("typo3.bs.popover",e)})}popover(t){t.each((t,o)=>{const e=new BootstrapPopover(o);$(o).data("typo3.bs.popover",e)})}setOptions(t,o){(o=o||{}).html=!0;const e=o.title||t.data("title")||"",a=o.content||t.data("bs-content")||"";t.attr("data-bs-original-title",e).attr("data-bs-content",a).attr("data-bs-placement","auto"),$.each(o,(o,e)=>{this.setOption(t,o,e)})}setOption(t,o,e){if("content"===o){const o=t.data("typo3.bs.popover");o._config.content=e,o.setContent(o.tip)}else t.each((t,a)=>{const p=$(a).data("typo3.bs.popover");p&&(p._config[o]=e)})}show(t){t.each((t,o)=>{const e=$(o).data("typo3.bs.popover");e&&e.show()})}hide(t){t.each((t,o)=>{const e=$(o).data("typo3.bs.popover");e&&e.hide()})}destroy(t){t.each((t,o)=>{const e=$(o).data("typo3.bs.popover");e&&e.dispose()})}toggle(t){t.each((t,o)=>{const e=$(o).data("typo3.bs.popover");e&&e.toggle()})}update(t){t.data("typo3.bs.popover")._popper.update()}}export default new Popover;