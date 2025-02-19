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
import d from"@typo3/backend/login.js";import l from"@typo3/core/event/regular-event.js";class p{constructor(){this.resetPassword=()=>{const e=document.querySelector(this.options.passwordField);if(e===null||e.value==="")return;const t=document.querySelector(d.options.useridentField);t&&(t.value=e.value),e.value=""},this.toggleCopyright=e=>{e.key===" "&&e.target.click()},this.attachCapslockWarning=(e,t,o)=>{const s=e.closest(".input-group");if(!s||s.querySelector(".input-group-text-warning-capslock"))return;const g=e.parentElement,u=`
      <span class="icon icon-size-small icon-state-default">
          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="m8 5.414 3.536 3.536.707-.707L8 4 3.757 8.243l.707.707L8 5.414zM4 11h8v1H4z"/></g></svg>
          </span>
      </span>
    `,c=document.createElement("span");c.classList.add("visually-hidden"),c.textContent=o;const a=document.createElement("span");if(a.classList.add("input-group-text","input-group-text-warning","input-group-text-warning-capslock"),a.role="status",a.innerHTML=u,a.title=t,a.appendChild(c),g.classList.contains("form-control-clearable-wrapper")){g.insertAdjacentElement("afterend",a);return}e.insertAdjacentElement("afterend",a)},this.removeCapslockWarning=e=>{const t=e.closest(".input-group");if(!t)return;const o=t.querySelector(".input-group-text-warning-capslock");o&&o.remove()},this.showCapslockWarning=e=>{const t=e.target,o=t.dataset.capslockwarningTitle,s=t.dataset.capslockwarningMessage;p.isCapslockEnabled(e)?this.attachCapslockWarning(t,o,s):this.removeCapslockWarning(t)},this.attachPasswordToggle=e=>{const t=e.closest(".input-group");if(!t||t.querySelector(".t3js-login-toggle-password"))return;const o=`
      <span class="icon icon-size-small icon-state-default">
        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="M8.07 3C4.112 3 1 5.286 1 8s2.97 5 7 5c3.889 0 7-2.286 7-4.93C15 5.285 11.889 3.142 8.212 3h-.141Zm-.025 1.127c.141 0 .423.141.423.282s-.14.282-.423.282c-.845 0-1.69.704-1.69 1.55 0 .14-.141.282-.423.282-.282 0-.423-.141-.423-.282.141-1.127 1.268-2.114 2.536-2.114ZM2 8.03c0-1.298 1.017-2.591 2.647-3.312-.296.432-.296 1.01-.296 1.587 0 2.02 1.63 3.606 3.703 3.606 2.074 0 3.704-1.587 3.704-3.606 0-.577-.148-1.01-.296-1.443C12.943 5.582 14 6.875 14 8.029c-.148 2.02-2.841 3.924-6 3.971-3.36-.047-6-1.95-6-3.97Z"/></g></svg>
        </span>
      </span>
    `,s=document.createElement("button");s.type="button",s.classList.add("btn","btn-default","t3js-login-toggle-password"),s.ariaLabel=e.dataset.passwordtoggleLabel??"",s.innerHTML=o,s.addEventListener("click",()=>{s.classList.contains("active")?(s.classList.remove("active"),e.type="password"):(s.classList.add("active"),e.type="text")}),t.insertAdjacentElement("beforeend",s)},this.removePasswordToggle=e=>{const t=e.closest(".input-group");if(!t)return;const o=t.querySelector(".t3js-login-toggle-password");o&&(o.remove(),e.type="password")},this.showPasswordToggle=e=>{const t=e.target;if(t.value===""){this.removePasswordToggle(t);return}else this.attachPasswordToggle(t)},this.options={usernameField:".t3js-login-username-field",passwordField:".t3js-login-password-field",copyrightLink:".t3js-login-copyright-link"};const r=document.querySelector(this.options.usernameField),n=document.querySelector(this.options.passwordField),i=document.querySelector(this.options.copyrightLink);d.options.submitHandler=this.resetPassword,[r,n].forEach(e=>new l("keypress",this.showCapslockWarning).bindTo(e)),["input","change"].forEach(e=>new l(e,this.showPasswordToggle).bindTo(n)),new l("keydown",this.toggleCopyright).bindTo(i),parent.opener?.TYPO3?.configuration?.username&&(r.value=parent.opener.TYPO3.configuration.username),r.value===""?r.focus():n.focus()}static isCapslockEnabled(r){const n=r||window.event;if(!n)return!1;let i=-1;n.which?i=n.which:n.keyCode&&(i=n.keyCode);let e=!1;return n.shiftKey?e=n.shiftKey:n.modifiers&&(e=!!(n.modifiers&4)),i>=65&&i<=90&&!e||i>=97&&i<=122&&e}}var w=new p;export{w as default};
