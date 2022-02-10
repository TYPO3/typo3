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
var InsertModes;!function(e){e.append="append",e.replace="replace",e.prepend="prepend"}(InsertModes||(InsertModes={}));export class ValuePicker extends HTMLElement{constructor(){super(...arguments),this.onChange=()=>{this.setValue(),this.valuePicker.selectedIndex=0,this.valuePicker.blur()}}connectedCallback(){this.valuePicker=this.querySelector("select"),null!==this.valuePicker&&this.valuePicker.addEventListener("change",this.onChange)}disconnectedCallback(){null!==this.valuePicker&&(this.valuePicker.removeEventListener("change",this.onChange),this.valuePicker=null)}setValue(){const e=this.valuePicker.options[this.valuePicker.selectedIndex].value,t=document.querySelector(this.getAttribute("linked-field")),n=this.getAttribute("mode")??InsertModes.replace;n===InsertModes.append?t.value+=e:n===InsertModes.prepend?t.value=e+t.value:t.value=e,t.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))}}window.customElements.define("typo3-formengine-valuepicker",ValuePicker);