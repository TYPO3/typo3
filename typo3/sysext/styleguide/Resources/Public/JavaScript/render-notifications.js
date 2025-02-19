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
import s from"@typo3/backend/notification.js";import c from"@typo3/backend/action-button/immediate-action.js";import d from"@typo3/backend/action-button/deferred-action.js";import m from"@typo3/core/event/regular-event.js";class f{constructor(){this.registerEvents()}registerEvents(){new m("click",(e,t)=>{const i=t.dataset.severity,n=t.dataset.title,o=t.dataset.message,r=parseInt(t.dataset.duration,10),a=t.dataset.includeActions==="1";s[i](n,o,r,this.createActions(a))}).delegateTo(document,'button[data-action="trigger-notification"]')}createActions(e){return e?[{label:"Immediate action",action:new c(function(){alert("Immediate action done")})},{label:"Deferred action",action:new d(function(){return new Promise(t=>setTimeout(()=>{alert("Deferred action done after 3000 ms"),t()},3e3))})}]:[]}}var l=new f;export{l as default};
