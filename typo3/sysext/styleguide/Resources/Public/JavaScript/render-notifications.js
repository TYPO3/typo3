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
import Notification from"@typo3/backend/notification.js";import ImmediateAction from"@typo3/backend/action-button/immediate-action.js";import DeferredAction from"@typo3/backend/action-button/deferred-action.js";import RegularEvent from"@typo3/core/event/regular-event.js";class RenderNotifications{constructor(){this.registerEvents()}registerEvents(){new RegularEvent("click",((t,e)=>{const i=e.dataset.severity,n=e.dataset.title,o=e.dataset.message,a=parseInt(e.dataset.duration,10),r="1"===e.dataset.includeActions;Notification[i](n,o,a,this.createActions(r))})).delegateTo(document,'button[data-action="trigger-notification"]')}createActions(t){return t?[{label:"Immediate action",action:new ImmediateAction((function(){alert("Immediate action done")}))},{label:"Deferred action",action:new DeferredAction((function(){return new Promise((t=>setTimeout((()=>{alert("Deferred action done after 3000 ms"),t()}),3e3)))}))}]:[]}}export default new RenderNotifications;