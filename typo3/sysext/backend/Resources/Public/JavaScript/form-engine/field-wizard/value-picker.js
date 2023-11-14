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
var InsertModes;!function(e){e.append="append",e.replace="replace",e.prepend="prepend"}(InsertModes||(InsertModes={}));export class ValuePicker extends HTMLElement{constructor(){super(...arguments),this.onChange=()=>{this.setValue(),this.valuePicker.blur()},this.linkedFieldOnChange=()=>{this.getInsertMode()===InsertModes.replace?this.selectValue(this.linkedField.value):this.valuePicker.selectedIndex=0}}connectedCallback(){if(this.valuePicker=this.querySelector("select"),null!==this.valuePicker&&this.valuePicker.addEventListener("change",this.onChange),this.linkedField=document.querySelector(this.getAttribute("linked-field")),null!==this.linkedField&&(this.linkedField.addEventListener("change",this.linkedFieldOnChange),this.getInsertMode()===InsertModes.replace)){const e=document.getElementsByName(this.linkedField.dataset.formengineInputName)[0];e&&this.selectValue(e.value)}}disconnectedCallback(){null!==this.valuePicker&&(this.valuePicker.removeEventListener("change",this.onChange),this.valuePicker=null)}selectValue(e){this.valuePicker.selectedIndex=Array.from(this.valuePicker.options).findIndex((t=>t.value===e))}getInsertMode(){return this.getAttribute("mode")??InsertModes.replace}setValue(){const e=this.valuePicker.options[this.valuePicker.selectedIndex].value;switch(this.getInsertMode()){case InsertModes.append:this.linkedField.value+=e;break;case InsertModes.prepend:this.linkedField.value=e+this.linkedField.value;break;default:this.linkedField.value=e}this.linkedField.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))}}window.customElements.define("typo3-formengine-valuepicker",ValuePicker);