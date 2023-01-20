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
import LinkBrowser from"@typo3/backend/link-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";class MailLinkHandler{constructor(){new RegularEvent("submit",((e,t)=>{e.preventDefault();const r=t.querySelector('[name="lemail"]').value,n=new URLSearchParams;for(const e of["subject","cc","bcc","body"]){const r=t.querySelector('[data-mailto-part="'+e+'"]');r?.value.length&&n.set(e,encodeURIComponent(r.value))}let o="mailto:"+r;[...n].length>0&&(o+="?"+n.toString()),LinkBrowser.finalizeFunction(o)})).delegateTo(document,"#lmailform")}}export default new MailLinkHandler;