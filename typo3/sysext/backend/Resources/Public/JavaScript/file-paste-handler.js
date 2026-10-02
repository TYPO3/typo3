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
import{html as r}from"lit";import c,{Sizes as d}from"@typo3/backend/modal.js";import{SeverityEnum as u}from"@typo3/backend/enum/severity.js";import i from"~labels/core.core";class l{constructor(){this.targets=new Set,this.lastInteractionTarget=null,this.trackInteraction=t=>{this.lastInteractionTarget=t.target instanceof Element?t.target:null},this.handlePaste=t=>{const e=t.clipboardData?.files;if(t.defaultPrevented||!e||e.length===0||t.target instanceof Element&&t.target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])')!==null)return;const a=this.findCandidates();if(a.length===0)return;t.preventDefault();const n=Array.from(e);a.length===1?a[0].receive(n):this.askForTarget(a,n)}}listen(t){t.addEventListener("pointerdown",this.trackInteraction,{capture:!0}),t.addEventListener("focusin",this.trackInteraction,{capture:!0}),t.addEventListener("paste",this.handlePaste)}register(t){this.targets.add(t)}findCandidates(){const t=new Map;for(const e of this.targets)e.scope!==null&&(e.scope.isConnected?t.set(e.scope,e):this.targets.delete(e));for(let e=this.lastInteractionTarget;e!==null;e=e.parentElement)if(t.has(e))return[t.get(e)];return[...this.targets].filter(e=>e.isAvailable())}askForTarget(t,e){const a=s=>{n.hideModal(),s.receive(e)},n=c.advanced({title:i.get("file_upload.pasteTarget.title"),content:r`<p>${i.get("file_upload.pasteTarget.message",{count:e.length})}</p><div class="d-grid gap-2">${t.map(s=>r`<button type=button class="btn btn-default" @click=${()=>a(s)}>${s.label}</button>`)}</div>`,severity:u.notice,size:d.small,buttons:[{text:i.get("file_upload.button.cancel"),btnClass:"btn-default",name:"cancel",trigger:()=>n.hideModal()}]})}}const o=new l;o.listen(document);export{l as FilePasteHandler,o as default};
