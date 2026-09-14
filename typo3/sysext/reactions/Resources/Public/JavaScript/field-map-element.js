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
const i=["unset","fixed","payload"],l=d=>i.includes(d);class n extends HTMLElement{constructor(){super(...arguments),this.handleChange=t=>{const e=t.target;if(!(e instanceof HTMLElement))return;if(e instanceof HTMLSelectElement&&e.hasAttribute("data-fieldmap-mode")){l(e.value)&&(this.mode=e.value,this.seedPayloadDefault(),this.syncValue());return}const a=e.closest("[data-fieldmap-pane]");a!==null&&a.dataset.fieldmapPane===this.mode&&this.syncValue()}}static{this.observedAttributes=["mode"]}static{this.watchedEvents=["change","input","blur"]}get mode(){const t=this.getAttribute("mode");return l(t)?t:"unset"}set mode(t){this.setAttribute("mode",t)}connectedCallback(){for(const t of n.watchedEvents)this.addEventListener(t,this.handleChange,!0);this.applyMode(),this.syncValue(!1)}disconnectedCallback(){for(const t of n.watchedEvents)this.removeEventListener(t,this.handleChange,!0)}attributeChangedCallback(){this.applyMode()}seedPayloadDefault(){if(this.mode!=="payload")return;const t=this.querySelector('[data-fieldmap-pane="payload"] input'),e=this.getAttribute("payload-default")??"";t!==null&&t.value===""&&e!==""&&(t.value=e)}syncValue(t=!0){const e=this.querySelector("[data-fieldmap-value]");if(e===null)return;let a="";if(this.mode!=="unset"){const s=this.querySelector(`[data-fieldmap-pane="${this.mode}"]`)?.dataset.fieldmapSource??"";a=(s===""?null:e.form?.elements.namedItem(s)??null)?.value??""}e.value!==a&&(e.value=a,t&&e.dispatchEvent(new Event("change",{bubbles:!0})))}applyMode(){for(const e of this.querySelectorAll("[data-fieldmap-pane]")){const a=e.dataset.fieldmapPane;e.hidden=a==="payload"?this.mode!=="payload":this.mode==="payload",e.inert=!e.hidden&&a!==this.mode}const t=this.querySelector("[data-fieldmap-value]");t!==null&&(t.disabled=this.mode==="unset")}}window.customElements.define("typo3-reactions-field-map-row",n);export{n as FieldMapRowElement};
