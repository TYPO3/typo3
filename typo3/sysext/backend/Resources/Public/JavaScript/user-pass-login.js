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
import i from"@typo3/backend/login.js";import r from"@typo3/core/event/regular-event.js";import n from"@typo3/core/document-service.js";class a{constructor(){this.resetPassword=()=>{const s=document.querySelector(this.options.passwordField);if(s===null||s.value==="")return;const e=document.querySelector(i.options.useridentField);e&&(e.value=s.value),s.value=""},this.toggleCopyright=s=>{s.key===" "&&s.target.click()},this.attachPasswordToggle=s=>{const e=s.closest(".input-group");if(!e||e.querySelector(".t3js-login-toggle-password"))return;const o=`
      <span class="icon icon-size-small icon-state-default">
        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="M8.07 3C4.112 3 1 5.286 1 8s2.97 5 7 5c3.889 0 7-2.286 7-4.93C15 5.285 11.889 3.142 8.212 3h-.141Zm-.025 1.127c.141 0 .423.141.423.282s-.14.282-.423.282c-.845 0-1.69.704-1.69 1.55 0 .14-.141.282-.423.282-.282 0-.423-.141-.423-.282.141-1.127 1.268-2.114 2.536-2.114ZM2 8.03c0-1.298 1.017-2.591 2.647-3.312-.296.432-.296 1.01-.296 1.587 0 2.02 1.63 3.606 3.703 3.606 2.074 0 3.704-1.587 3.704-3.606 0-.577-.148-1.01-.296-1.443C12.943 5.582 14 6.875 14 8.029c-.148 2.02-2.841 3.924-6 3.971-3.36-.047-6-1.95-6-3.97Z"/></g></svg>
        </span>
      </span>
    `,t=document.createElement("button");t.type="button",t.classList.add("btn","btn-default","t3js-login-toggle-password"),t.ariaLabel=s.dataset.passwordtoggleLabel??"",t.innerHTML=o,t.addEventListener("click",()=>{t.classList.contains("active")?(t.classList.remove("active"),s.type="password"):(t.classList.add("active"),s.type="text")}),e.insertAdjacentElement("beforeend",t)},this.removePasswordToggle=s=>{const e=s.closest(".input-group");if(!e)return;const o=e.querySelector(".t3js-login-toggle-password");o&&(o.remove(),s.type="password")},this.showPasswordToggle=s=>{const e=s.target;if(e.value===""){this.removePasswordToggle(e);return}else this.attachPasswordToggle(e)},this.init()}async init(){await n.ready(),this.options={usernameField:".t3js-login-username-field",passwordField:".t3js-login-password-field",copyrightLink:".t3js-login-copyright-link"};const s=document.querySelector(this.options.usernameField),e=document.querySelector(this.options.passwordField),o=document.querySelector(this.options.copyrightLink);i.options.submitHandler=this.resetPassword,["input","change"].forEach(t=>new r(t,this.showPasswordToggle).bindTo(e)),new r("keydown",this.toggleCopyright).bindTo(o),parent.opener?.TYPO3?.configuration?.username&&(s.value=parent.opener.TYPO3.configuration.username),s.value===""?s.focus():e.focus()}}var c=new a;export{c as default};
