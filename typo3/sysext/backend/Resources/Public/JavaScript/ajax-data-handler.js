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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import Notification from"@typo3/backend/notification.js";class AjaxDataHandler{static call(a){return new AjaxRequest(TYPO3.settings.ajaxUrls.record_process).withQueryArguments(a).get().then((async a=>await a.resolve()))}async process(a,e){return AjaxDataHandler.call(a).then((a=>{if(a.hasErrors&&this.handleErrors(a),e){const r={...e,hasErrors:a.hasErrors},t=new BroadcastMessage("datahandler","process",r);BroadcastService.post(t);const s=new CustomEvent("typo3:datahandler:process",{detail:{payload:r}});document.dispatchEvent(s)}return a}))}handleErrors(a){for(const e of a.messages)Notification.error(e.title,e.message)}}export default new AjaxDataHandler;