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
import{BroadcastMessage as c}from"@typo3/backend/broadcast-message.js";import i from"@typo3/core/ajax/ajax-request.js";import m from"@typo3/backend/broadcast-service.js";import d from"@typo3/backend/notification.js";class a{static call(s){return new i(TYPO3.settings.ajaxUrls.record_process).withQueryArguments(s).get().then(async r=>await r.resolve())}async process(s,r){return a.call(s).then(e=>{if(e.hasErrors&&this.handleErrors(e),r){const o={...r,hasErrors:e.hasErrors},t=new c("datahandler","process",o);m.post(t);const n=new CustomEvent("typo3:datahandler:process",{detail:{payload:o}});document.dispatchEvent(n)}return e})}handleErrors(s){for(const r of s.messages)d.error(r.title,r.message)}}var p=new a;export{p as default};
