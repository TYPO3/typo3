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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifier;!function(e){e.toggleAll=".t3js-toggle-checkboxes",e.singleItem=".t3js-checkbox",e.revertSelection=".t3js-revert-selection"}(Identifier||(Identifier={}));class SelectCheckBoxElement{constructor(e){this.checkBoxId="",this.table=null,this.checkedBoxes=null,this.checkBoxId=e,DocumentService.ready().then((t=>{this.table=t.getElementById(e).closest("table"),this.checkedBoxes=this.table.querySelectorAll(Identifier.singleItem+":checked"),this.enableTriggerCheckBox(),this.registerEventHandler()}))}static allCheckBoxesAreChecked(e){const t=Array.from(e);return e.length===t.filter((e=>e.checked)).length}registerEventHandler(){new RegularEvent("change",((e,t)=>{const l=this.table.querySelectorAll(Identifier.singleItem),c=!SelectCheckBoxElement.allCheckBoxesAreChecked(l);l.forEach((e=>{e.checked=c})),t.checked=c})).delegateTo(this.table,Identifier.toggleAll),new RegularEvent("change",this.setToggleAllState.bind(this)).delegateTo(this.table,Identifier.singleItem),new RegularEvent("click",(()=>{const e=this.table.querySelectorAll(Identifier.singleItem),t=Array.from(this.checkedBoxes);e.forEach((e=>{e.checked=t.includes(e)})),this.setToggleAllState()})).delegateTo(this.table,Identifier.revertSelection)}setToggleAllState(){const e=this.table.querySelectorAll(Identifier.singleItem);this.table.querySelector(Identifier.toggleAll).checked=SelectCheckBoxElement.allCheckBoxesAreChecked(e)}enableTriggerCheckBox(){const e=this.table.querySelectorAll(Identifier.singleItem);document.getElementById(this.checkBoxId).checked=SelectCheckBoxElement.allCheckBoxesAreChecked(e)}}export default SelectCheckBoxElement;