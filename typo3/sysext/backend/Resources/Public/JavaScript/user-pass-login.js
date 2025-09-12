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
import c from"@typo3/backend/login.js";import r from"@typo3/core/event/regular-event.js";import p from"@typo3/core/document-service.js";class g{constructor(){this.resetPassword=()=>{const t=document.querySelector(this.options.passwordField);if(t===null||t.value==="")return;const e=document.querySelector(c.options.useridentField);e&&(e.value=t.value),t.value=""},this.toggleCopyright=t=>{t.key===" "&&t.target.click()},this.attachCapslockWarning=(t,e,n)=>{const s=t.closest(".input-group");if(!s||s.querySelector(".input-group-text-warning-capslock"))return;const a=t.parentElement,l=`
      <span class="icon icon-size-small icon-state-default">
          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="m8 5.414 3.536 3.536.707-.707L8 4 3.757 8.243l.707.707L8 5.414zM4 11h8v1H4z"/></g></svg>
          </span>
      </span>
    `,i=document.createElement("span");i.classList.add("visually-hidden"),i.textContent=n;const o=document.createElement("span");if(o.classList.add("input-group-text","input-group-text-warning","input-group-text-warning-capslock"),o.role="status",o.innerHTML=l,o.title=e,o.appendChild(i),a.classList.contains("form-control-clearable-wrapper")){a.insertAdjacentElement("afterend",o);return}t.insertAdjacentElement("afterend",o)},this.removeCapslockWarning=t=>{const e=t.closest(".input-group");if(!e)return;const n=e.querySelector(".input-group-text-warning-capslock");n&&n.remove()},this.showCapslockWarning=t=>{const e=t.target,n=e.dataset.capslockwarningTitle,s=e.dataset.capslockwarningMessage;this.isCapslockEnabled(t)?this.attachCapslockWarning(e,n,s):this.removeCapslockWarning(e)},this.attachPasswordToggle=t=>{const e=t.closest(".input-group");if(!e||e.querySelector(".t3js-login-toggle-password"))return;const n=`
      <span class="icon icon-size-small icon-state-default">
        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="M8.07 3C4.112 3 1 5.286 1 8s2.97 5 7 5c3.889 0 7-2.286 7-4.93C15 5.285 11.889 3.142 8.212 3h-.141Zm-.025 1.127c.141 0 .423.141.423.282s-.14.282-.423.282c-.845 0-1.69.704-1.69 1.55 0 .14-.141.282-.423.282-.282 0-.423-.141-.423-.282.141-1.127 1.268-2.114 2.536-2.114ZM2 8.03c0-1.298 1.017-2.591 2.647-3.312-.296.432-.296 1.01-.296 1.587 0 2.02 1.63 3.606 3.703 3.606 2.074 0 3.704-1.587 3.704-3.606 0-.577-.148-1.01-.296-1.443C12.943 5.582 14 6.875 14 8.029c-.148 2.02-2.841 3.924-6 3.971-3.36-.047-6-1.95-6-3.97Z"/></g></svg>
        </span>
      </span>
    `,s=document.createElement("button");s.type="button",s.classList.add("btn","btn-default","t3js-login-toggle-password"),s.ariaLabel=t.dataset.passwordtoggleLabel??"",s.innerHTML=n,s.addEventListener("click",()=>{s.classList.contains("active")?(s.classList.remove("active"),t.type="password"):(s.classList.add("active"),t.type="text")}),e.insertAdjacentElement("beforeend",s)},this.removePasswordToggle=t=>{const e=t.closest(".input-group");if(!e)return;const n=e.querySelector(".t3js-login-toggle-password");n&&(n.remove(),t.type="password")},this.showPasswordToggle=t=>{const e=t.target;if(e.value===""){this.removePasswordToggle(e);return}else this.attachPasswordToggle(e)},this.init()}async init(){await p.ready(),this.options={usernameField:".t3js-login-username-field",passwordField:".t3js-login-password-field",copyrightLink:".t3js-login-copyright-link"};const t=document.querySelector(this.options.usernameField),e=document.querySelector(this.options.passwordField),n=document.querySelector(this.options.copyrightLink);c.options.submitHandler=this.resetPassword,[t,e].forEach(s=>new r("keypress",this.showCapslockWarning).bindTo(s)),["input","change"].forEach(s=>new r(s,this.showPasswordToggle).bindTo(e)),new r("keydown",this.toggleCopyright).bindTo(n),parent.opener?.TYPO3?.configuration?.username&&(t.value=parent.opener.TYPO3.configuration.username),t.value===""?t.focus():e.focus()}isCapslockEnabled(t){const e=t||window.event;if(!e)return!1;let n=-1;e.which?n=e.which:e.keyCode&&(n=e.keyCode);let s=!1;return e.shiftKey?s=e.shiftKey:e.modifiers&&(s=!!(e.modifiers&4)),n>=65&&n<=90&&!s||n>=97&&n<=122&&s}}var d=new g;export{d as default};
