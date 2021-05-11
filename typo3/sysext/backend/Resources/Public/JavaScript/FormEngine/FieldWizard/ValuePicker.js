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
define(["require","exports"],(function(e,t){"use strict";var l;Object.defineProperty(t,"__esModule",{value:!0}),t.ValuePicker=void 0,function(e){e.append="append",e.replace="replace",e.prepend="prepend"}(l||(l={}));class n extends HTMLElement{constructor(){super(...arguments),this.onChange=()=>{this.setValue(),this.valuePicker.selectedIndex=0,this.valuePicker.blur()}}connectedCallback(){this.valuePicker=this.querySelector("select"),null!==this.valuePicker&&this.valuePicker.addEventListener("change",this.onChange)}disconnectedCallback(){null!==this.valuePicker&&(this.valuePicker.removeEventListener("change",this.onChange),this.valuePicker=null)}setValue(){var e;const t=this.valuePicker.options[this.valuePicker.selectedIndex].value,n=document.querySelector(this.getAttribute("linked-field")),i=null!==(e=this.getAttribute("mode"))&&void 0!==e?e:l.replace;i===l.append?n.value+=t:i===l.prepend?n.value=t+n.value:n.value=t,n.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))}}t.ValuePicker=n,window.customElements.define("typo3-formengine-valuepicker",n)}));