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
import r from"@typo3/backend/link-browser.js";import m from"@typo3/core/event/regular-event.js";class c{constructor(){new m("submit",(o,t)=>{o.preventDefault();const i=t.querySelector('[name="lemail"]').value,e=new URLSearchParams;for(const a of["subject","cc","bcc","body"]){const l=t.querySelector('[data-mailto-part="'+a+'"]');l?.value.length&&e.set(a,encodeURIComponent(l.value))}let n="mailto:"+i;[...e].length>0&&(n+="?"+e.toString()),r.finalizeFunction(n)}).delegateTo(document,"#lmailform")}}var u=new c;export{u as default};
