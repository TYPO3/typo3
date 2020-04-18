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
define(["require","exports","TYPO3/CMS/Core/DocumentService"],(function(e,t,n){"use strict";return new class{constructor(){this.options={onChangeSelector:'[data-global-event="change"]',onClickSelector:'[data-global-event="click"]'},n.ready().then(()=>this.registerEvents())}registerEvents(){document.querySelectorAll(this.options.onChangeSelector).forEach(e=>{document.addEventListener("change",this.handleChangeEvent.bind(this))}),document.querySelectorAll(this.options.onClickSelector).forEach(e=>{document.addEventListener("click",this.handleClickEvent.bind(this))})}handleChangeEvent(e){const t=e.target;this.handleSubmitAction(e,t)||this.handleNavigateAction(e,t)}handleClickEvent(e){e.currentTarget}handleSubmitAction(e,t){const n=t.dataset.actionSubmit;if(!n)return!1;if("$form"===n&&this.isHTMLFormChildElement(t))return t.form.submit(),!0;const i=document.querySelector(n);return i instanceof HTMLFormElement&&(i.submit(),!0)}handleNavigateAction(e,t){const n=t.dataset.actionNavigate;if(!n)return!1;const i=this.resolveHTMLFormChildElementValue(t);return!("$value"!==n||!i)&&(window.location.href=i,!0)}isHTMLFormChildElement(e){return e instanceof HTMLSelectElement||e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement}resolveHTMLFormChildElementValue(e){return e instanceof HTMLSelectElement?e.options[e.selectedIndex].value:e instanceof HTMLInputElement?e.value:null}}}));