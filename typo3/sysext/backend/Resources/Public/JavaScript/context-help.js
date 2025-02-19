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
import"bootstrap";import i from"@typo3/backend/popover.js";import r from"@typo3/core/event/regular-event.js";import c from"@typo3/core/document-service.js";class n{constructor(){this.trigger="click",this.placement="auto",this.selector=".help-link",this.initialize()}async initialize(){await c.ready();const s=document.querySelectorAll(this.selector);s.forEach(t=>{t.dataset.bsHtml="true",t.dataset.bsPlacement=this.placement,t.dataset.bsTrigger=this.trigger,i.popover(t)}),new r("show.bs.popover",t=>{const e=t.target,o=e.dataset.description;if(o){const a={title:e.dataset.title||"",content:o};i.setOptions(e,a)}}).delegateTo(document,this.selector),new r("click",t=>{const e=t.target;s.forEach(o=>{o.isEqualNode(e)||i.hide(o)})}).delegateTo(document,"body")}}var l=new n;export{l as default};
