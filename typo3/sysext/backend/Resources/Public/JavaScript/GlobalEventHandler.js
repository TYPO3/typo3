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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,i){"use strict";return new class{constructor(){this.options={onChangeSelector:'[data-global-event="change"]',onClickSelector:'[data-global-event="click"]'},n.ready().then(()=>this.registerEvents())}registerEvents(){new i("change",this.handleChangeEvent.bind(this)).delegateTo(document,this.options.onChangeSelector),new i("click",this.handleClickEvent.bind(this)).delegateTo(document,this.options.onClickSelector)}handleChangeEvent(e,t){e.preventDefault(),this.handleFormChildSubmitAction(e,t)||this.handleFormChildNavigateAction(e,t)}handleClickEvent(e,t){e.preventDefault()}handleFormChildSubmitAction(e,t){const n=t.dataset.actionSubmit;if(!n)return!1;if("$form"===n&&this.isHTMLFormChildElement(t))return t.form.submit(),!0;const i=document.querySelector(n);return i instanceof HTMLFormElement&&(i.submit(),!0)}handleFormChildNavigateAction(e,t){const n=t.dataset.actionNavigate;if(!n)return!1;const i=this.resolveHTMLFormChildElementValue(t),a=t.dataset.navigateValue;return"$data=~s/$value/"===n&&a&&null!==i?(window.location.href=this.substituteValueVariable(a,i),!0):"$data"===n&&a?(window.location.href=a,!0):!("$value"!==n||!i)&&(window.location.href=i,!0)}substituteValueVariable(e,t){return e.replace(/(\$\{value\}|%24%7Bvalue%7D|\$\[value\]|%24%5Bvalue%5D)/gi,t)}isHTMLFormChildElement(e){return e instanceof HTMLSelectElement||e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement}resolveHTMLFormChildElementValue(e){const t=e.getAttribute("type");return e instanceof HTMLSelectElement?e.options[e.selectedIndex].value:e instanceof HTMLInputElement&&"checkbox"===t?e.checked?e.value:"":e instanceof HTMLInputElement?e.value:null}}}));