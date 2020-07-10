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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,l,c){"use strict";var s;!function(e){e.toggleAll=".t3js-toggle-checkboxes",e.singleItem=".t3js-checkbox",e.revertSelection=".t3js-revert-selection"}(s||(s={}));class r{constructor(e){this.checkBoxId="",this.table=null,this.checkedBoxes=null,this.checkBoxId=e,l.ready().then(t=>{this.table=t.getElementById(e).closest("table"),this.checkedBoxes=this.table.querySelectorAll(s.singleItem+":checked"),this.enableTriggerCheckBox(),this.registerEventHandler()})}static allCheckBoxesAreChecked(e){const t=Array.from(e);return e.length===t.filter(e=>e.checked).length}registerEventHandler(){new c("change",(e,t)=>{const l=this.table.querySelectorAll(s.singleItem),c=!r.allCheckBoxesAreChecked(l);l.forEach(e=>{e.checked=c}),t.checked=c}).delegateTo(this.table,s.toggleAll),new c("change",this.setToggleAllState.bind(this)).delegateTo(this.table,s.singleItem),new c("click",()=>{const e=this.table.querySelectorAll(s.singleItem),t=Array.from(this.checkedBoxes);e.forEach(e=>{e.checked=t.includes(e)}),this.setToggleAllState()}).delegateTo(this.table,s.revertSelection)}setToggleAllState(){const e=this.table.querySelectorAll(s.singleItem);this.table.querySelector(s.toggleAll).checked=r.allCheckBoxesAreChecked(e)}enableTriggerCheckBox(){const e=this.table.querySelectorAll(s.singleItem);document.getElementById(this.checkBoxId).checked=r.allCheckBoxesAreChecked(e)}}return r}));