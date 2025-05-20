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
import{BroadcastMessage as c}from"@typo3/backend/broadcast-message.js";import d from"@typo3/core/ajax/ajax-request.js";import i from"@typo3/backend/broadcast-service.js";import m from"@typo3/backend/notification.js";import{sudoModeInterceptor as p}from"@typo3/backend/security/sudo-mode-interceptor.js";class a{static call(e){return new d(TYPO3.settings.ajaxUrls.record_process).addMiddleware(p).withQueryArguments(e).get().then(async r=>await r.resolve())}async process(e,r){return a.call(e).then(s=>{if(s.hasErrors&&this.handleErrors(s),r){const o={...r,hasErrors:s.hasErrors},t=new c("datahandler","process",o);i.post(t);const n=new CustomEvent("typo3:datahandler:process",{detail:{payload:o}});document.dispatchEvent(n)}return s})}handleErrors(e){for(const r of e.messages)m.error(r.title,r.message)}}var l=new a;export{l as default};
