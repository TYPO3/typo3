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
import i from"@typo3/backend/icons.js";class s{constructor(t){this.isSubmitting=!1,this.preSubmitCallbacks=[],t.addEventListener("submit",this.submitHandler.bind(this))}addPreSubmitCallback(t){if(typeof t!="function")throw"callback must be a function.";return this.preSubmitCallbacks.push(t),this}submitHandler(t){if(!this.isSubmitting){for(const e of this.preSubmitCallbacks)if(!e(t)){t.preventDefault();return}this.isSubmitting=!0,t.submitter!==null&&((t.submitter instanceof HTMLInputElement||t.submitter instanceof HTMLButtonElement)&&(t.submitter.disabled=!0),i.getIcon("spinner-circle",i.sizes.small).then(e=>{t.submitter.replaceChild(document.createRange().createContextualFragment(e),t.submitter.querySelector(".t3js-icon"))}).catch(()=>{}))}}}export{s as default};
