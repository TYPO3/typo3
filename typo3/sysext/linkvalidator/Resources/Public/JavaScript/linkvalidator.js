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
import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Selectors,Identifier;!function(e){e.actionButtonSelector=".t3js-linkvalidator-action-button",e.toggleAllLinktypesSelector='.t3js-linkvalidator-settings input[type="checkbox"].options-by-type-toggle-all',e.linktypesSelector='.t3js-linkvalidator-settings input[type="checkbox"].options-by-type'}(Selectors||(Selectors={})),function(e){e.toggleAllLinktypesId="options-by-type-toggle-all"}(Identifier||(Identifier={}));class Linkvalidator{constructor(){this.toggleTriggerCheckBox(),this.toggleActionButton(),this.initializeEvents()}static allCheckBoxesAreChecked(e){const t=Array.from(e);return e.length===t.filter((e=>e.checked)).length}toggleActionButton(){document.querySelector(Selectors.actionButtonSelector)?.toggleAttribute("disabled",!document.querySelectorAll('input[type="checkbox"]:checked').length)}toggleTriggerCheckBox(){const e=document.querySelectorAll(Selectors.linktypesSelector);document.getElementById(Identifier.toggleAllLinktypesId).checked=Linkvalidator.allCheckBoxesAreChecked(e)}initializeEvents(){new RegularEvent("change",((e,t)=>{const o=document.querySelectorAll(Selectors.linktypesSelector),l=!Linkvalidator.allCheckBoxesAreChecked(o);o.forEach((e=>{e.checked=l})),t.checked=l,this.toggleActionButton()})).delegateTo(document,Selectors.toggleAllLinktypesSelector),new RegularEvent("change",(()=>{this.toggleTriggerCheckBox(),this.toggleActionButton()})).delegateTo(document,Selectors.linktypesSelector),new RegularEvent("click",((e,t)=>{Notification.success(t.dataset.notificationMessage||"Event triggered","",2)})).delegateTo(document,Selectors.actionButtonSelector)}}export default new Linkvalidator;