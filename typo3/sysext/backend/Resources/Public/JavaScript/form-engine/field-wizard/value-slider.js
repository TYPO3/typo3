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
import r from"@typo3/core/document-service.js";import u from"@typo3/core/event/throttle-event.js";var i;(function(a){a.integer="integer",a.decimal="decimal"})(i||(i={}));class n extends HTMLElement{constructor(){super(...arguments),this.valueSlider=null,this.handleRangeChange=t=>{const e=t.target;this.updateValue(e),this.updateTooltipValue(e)}}async connectedCallback(){await r.ready(),this.valueSlider=this.querySelector("input"),this.valueSlider!==null&&new u("input",this.handleRangeChange,25).bindTo(this.valueSlider)}updateValue(t){const e=document.querySelector(this.getAttribute("linked-field"));e.value=t.value,e.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))}updateTooltipValue(t){let e;const l=t.value;switch(this.getAttribute("format")){case i.decimal:e=parseFloat(l).toFixed(Number(this.getAttribute("precision"))||2);break;case i.integer:default:e=parseInt(l,10)}t.title=e.toString()}}window.customElements.define("typo3-formengine-valueslider",n);export{n as ValueSlider};
