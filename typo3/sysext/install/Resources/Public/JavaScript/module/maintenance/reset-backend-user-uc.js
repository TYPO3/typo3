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
import o from"@typo3/core/ajax/ajax-request.js";import{AbstractInlineModule as n}from"@typo3/install/module/abstract-inline-module.js";import s from"@typo3/backend/notification.js";import c from"@typo3/install/router.js";class i extends n{initialize(t){this.setButtonState(t,!1),new o(c.getUrl("resetBackendUserUc")).get({cache:"no-cache"}).then(async a=>{const e=await a.resolve();e.success===!0&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(r=>{s.success(r.title,r.message)}):s.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{s.error("Reset preferences of all backend users failed","Resetting preferences of all backend users failed for an unknown reason. Please check your server's logs for further investigation.")}).finally(()=>{this.setButtonState(t,!0)})}}var l=new i;export{l as default};
