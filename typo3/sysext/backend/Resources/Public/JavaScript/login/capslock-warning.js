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
import o from"@typo3/core/event/regular-event.js";import i from"@typo3/core/document-service.js";class c{constructor(){this.updateWarning=n=>{if(!(n instanceof KeyboardEvent))return;const t=n.target;n.getModifierState("CapsLock")?this.attachWarning(t):this.removeWarning(t)},i.ready().then(()=>{document.querySelectorAll(".t3js-capslockwarning").forEach(n=>this.bindTo(n))})}bindTo(n){["keydown","keyup"].forEach(t=>new o(t,this.updateWarning).bindTo(n))}attachWarning(n){const t=n.closest(".input-group");if(!t||t.querySelector(".input-group-text-warning-capslock"))return;const s=`
      <span class="icon icon-size-small icon-state-default">
          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="m8 5.414 3.536 3.536.707-.707L8 4 3.757 8.243l.707.707L8 5.414zM4 11h8v1H4z"/></g></svg>
          </span>
      </span>
    `,a=document.createElement("span");a.classList.add("visually-hidden"),a.textContent=n.dataset.capslockwarningMessage??"";const e=document.createElement("span");e.classList.add("input-group-text","input-group-text-warning","input-group-text-warning-capslock"),e.role="status",e.innerHTML=s,e.title=n.dataset.capslockwarningTitle??"",e.appendChild(a);const r=n.parentElement;if(r.classList.contains("form-control-clearable-wrapper")){r.insertAdjacentElement("afterend",e);return}n.insertAdjacentElement("afterend",e)}removeWarning(n){n.closest(".input-group")?.querySelector(".input-group-text-warning-capslock")?.remove()}}var l=new c;export{l as default};
