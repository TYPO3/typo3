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
import t from"@typo3/backend/modal.js";import{SeverityEnum as a}from"@typo3/backend/enum/severity.js";import n from"@typo3/core/event/regular-event.js";class l{constructor(){this.selector=".t3js-linkvalidator-modal",this.initialize()}initialize(){new n("click",function(o){o.preventDefault();const e=new DocumentFragment;e.append(document.getElementById(`linkvalidatorModal-${this.dataset.modalIdentifier}`).content.cloneNode(!0));const i={type:t.types.default,title:this.dataset.modalTitle,size:t.sizes.large,severity:a.notice,content:e};t.advanced(i)}).delegateTo(document,this.selector)}}var d=new l;export{d as default};
