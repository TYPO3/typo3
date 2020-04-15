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
define(["require","exports"],(function(e,t){"use strict";return new class{constructor(){this.options={onChangeSelector:'[data-global-event="change"]',onClickSelector:'[data-global-event="click"]'},this.onReady(()=>{this.registerEvents()})}onReady(e){if("complete"===document.readyState)e.call(this);else{const t=()=>{window.removeEventListener("load",t),document.removeEventListener("DOMContentLoaded",t),e.call(this)};window.addEventListener("load",t),document.addEventListener("DOMContentLoaded",t)}}registerEvents(){document.querySelectorAll(this.options.onChangeSelector).forEach(e=>{document.addEventListener("change",this.handleChangeEvent.bind(this))}),document.querySelectorAll(this.options.onClickSelector).forEach(e=>{document.addEventListener("click",this.handleClickEvent.bind(this))})}handleChangeEvent(e){const t=e.target;this.handleSubmitAction(e,t)||this.handleNavigateAction(e,t)}handleClickEvent(e){e.currentTarget}handleSubmitAction(e,t){const n=t.dataset.actionSubmit;if(!n)return!1;if("$form"===n&&this.isHTMLFormChildElement(t))return t.form.submit(),!0;const o=document.querySelector(n);return o instanceof HTMLFormElement&&(o.submit(),!0)}handleNavigateAction(e,t){const n=t.dataset.actionNavigate;if(!n)return!1;const o=this.resolveHTMLFormChildElementValue(t);return!("$value"!==n||!o)&&(window.location.href=o,!0)}isHTMLFormChildElement(e){return e instanceof HTMLSelectElement||e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement}resolveHTMLFormChildElementValue(e){return e instanceof HTMLSelectElement?e.options[e.selectedIndex].value:e instanceof HTMLInputElement?e.value:null}}}));