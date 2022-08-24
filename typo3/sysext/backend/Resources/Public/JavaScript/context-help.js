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
import"bootstrap";import $ from"jquery";import Popover from"@typo3/backend/popover.js";class ContextHelp{constructor(){this.trigger="click",this.placement="auto",this.selector=".help-link",this.initialize()}initialize(){const t=$(this.selector);t.attr("data-bs-html","true").attr("data-bs-placement",this.placement).attr("data-bs-trigger",this.trigger),Popover.popover(t),$(document).on("show.bs.popover",this.selector,(t=>{const e=$(t.currentTarget),o=e.data("description");if(void 0!==o&&""!==o){const t={title:e.data("title")||"",content:o};Popover.setOptions(e,t)}})).on("click","body",(t=>{$(this.selector).each(((e,o)=>{const r=$(o);r.is(t.target)||0!==r.has(t.target).length||0!==$(".popover").has(t.target).length||Popover.hide(r)}))}))}}export default new ContextHelp;