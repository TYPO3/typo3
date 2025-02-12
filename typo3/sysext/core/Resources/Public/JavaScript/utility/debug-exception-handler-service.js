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
document.addEventListener("DOMContentLoaded",()=>{function g(t){const e=document.querySelector("div[data-project-path]");if(!(e instanceof HTMLElement))return t;const o=e?.dataset.projectPath||"";return!o||!t.startsWith(o)?t:t.substring(o.length)}function f(){const t=document.createElement("button");return t.className="copy-button",t.setAttribute("title","Copy file path and line-number to clipboard"),t.innerHTML=s(),t}function s(){return`<svg aria-label="Copy" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg> <span>Copy path</span>`}function d(){return`<svg aria-label="Green checkmark" width="16" height="12" viewBox="0 0 16 16" fill="green" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 8L6 12L14 4" stroke="green" stroke-width="2" fill="none"/>
            </svg> <span>Successfully copied to clipboard!</span>`}function u(){return`<svg aria-label="Error" width="12" height="12" viewBox="0 0 16 16" fill="red" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 3L13 13M13 3L3 13" stroke="red" stroke-width="2" fill="none"/>
            </svg> <span>Could not copy to clipboard (https only)!</span>`}function p(t){return t.trim().split(`
`).map(e=>e.trimStart()).map(e=>e.replace(/ {2,}/g," ")).join(`
`).replace(/\n{3,}/g,`

`)}document.querySelectorAll(".trace-file-path strong").forEach(t=>{if(t instanceof HTMLElement){const e=f();e.addEventListener("click",()=>{const o=g(t.textContent?.trim()||""),c=t.getAttribute("data-lineno")||"",r=`${o}:${c}`;try{navigator.clipboard.writeText(r).then(()=>{e.innerHTML=d(),setTimeout(()=>e.innerHTML=s(),5e3)})}catch{e.innerHTML=u()}}),t.parentElement.after(e)}});const i=document.createElement("div");i.className="trace-toggle",i.innerHTML=`
  <div class="callout" id="stack-export">
      <div class="callout-title">Stack Trace</div>
      <div class="callout-body">
          <p>
              You can copy the contents of this stack trace into the clipboard, to paste this error and get help.
              Be sure to scan it for any sensitive data, which you might want to redact.
              You can toggle the stack trace between a full and a minimal view (without file contents).
          </p>
      </div>
  </div>
  <div id="plaintextFallback"></div>
  `;const a=document.createElement("button");a.textContent="Toggle details",a.className="stacktrace-action-button",a.setAttribute("title","Toggle visibility of stack trace between full and minimal view (without file contents)"),a.addEventListener("click",()=>{document.querySelectorAll(".trace-file-content").forEach(t=>{t instanceof HTMLElement&&(t.style.display=t.style.display==="none"?"":"none")})});const n=document.createElement("button"),h="Copy plaintext stack trace";n.textContent=h,n.className="stacktrace-action-button",n.setAttribute("title","Copy plaintext stack trace to clipboard"),n.addEventListener("click",()=>{const t=document.querySelector(".trace")?.cloneNode(!0);if(t){t.querySelectorAll(".trace-file-content").forEach(e=>e.replaceWith(document.createTextNode(`
`))),t.querySelectorAll(".copy-button").forEach(e=>e.remove()),t.querySelectorAll("div").forEach(e=>e.appendChild(document.createTextNode(`
`))),t.querySelectorAll("span").forEach(e=>e.appendChild(document.createTextNode(" ")));try{navigator.clipboard.writeText(p(t.innerText)).then(()=>{n.innerHTML=d(),setTimeout(()=>n.innerHTML=h,5e3)})}catch{n.innerHTML=u();const e=document.createElement("pre");e.className="plaintextFallback",e.innerText=p(t.innerText);const o=document.getElementById("plaintextFallback");o.replaceChildren(e),o.scrollIntoView({behavior:"smooth",block:"center"});try{const c=document.createRange();c.selectNodeContents(e);const r=window.getSelection();r.removeAllRanges(),r.addRange(c)}catch{}}}});const l=document.querySelector("#stacktrace-action-buttons");l&&(l.appendChild(a),l.appendChild(n));const m=document.querySelector(".trace");m&&m.after(i)});
