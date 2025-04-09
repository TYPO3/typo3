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
import l from"@typo3/core/document-service.js";import i from"@typo3/backend/form-engine.js";import n from"@typo3/backend/form-engine-validation.js";import o from"@typo3/core/ajax/ajax-request.js";import s from"@typo3/backend/notification.js";class r{constructor(a){this.controlElement=null,this.humanReadableField=null,this.hiddenField=null,this.passwordRules=null,l.ready().then(()=>{if(this.controlElement=document.getElementById(a),this.humanReadableField=document.querySelector('input[data-formengine-input-name="'+this.controlElement.dataset.itemName+'"]'),this.hiddenField=document.querySelector('input[name="'+this.controlElement.dataset.itemName+'"]'),this.passwordRules=JSON.parse(this.controlElement.dataset.passwordRules||"{}"),!this.controlElement.dataset.allowEdit&&(this.humanReadableField.disabled=!0,this.humanReadableField.readOnly=!0,this.humanReadableField.isClearable||this.humanReadableField.classList.contains("t3js-clearable"))){this.humanReadableField.classList.remove("t3js-clearable");const e=this.humanReadableField.closest("div.form-control-clearable-wrapper");if(e){e.classList.remove("form-control-clearable");const t=e.querySelector("button.close");t&&e.removeChild(t)}}this.controlElement.addEventListener("click",this.generatePassword.bind(this))})}generatePassword(a){a.preventDefault(),new o(TYPO3.settings.ajaxUrls.password_generate).post({passwordRules:this.passwordRules}).then(async e=>{const t=await e.resolve();t.success===!0?(this.humanReadableField.type="text",this.humanReadableField.value=t.password,this.humanReadableField.dispatchEvent(new Event("change")),this.humanReadableField.value=this.hiddenField.value,n.validateField(this.humanReadableField),i.markFieldAsChanged(this.humanReadableField)):s.warning(t.message||"No password was generated")}).catch(()=>{s.error("Password could not be generated")})}}export{r as default};
