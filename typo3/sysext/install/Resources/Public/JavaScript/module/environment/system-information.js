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
import s from"@typo3/backend/notification.js";import r from"@typo3/core/ajax/ajax-request.js";import a from"@typo3/install/router.js";import{AbstractInteractableModule as n}from"@typo3/install/module/abstract-interactable-module.js";class i extends n{initialize(e){super.initialize(e),this.getData()}getData(){const e=this.getModalBody();new r(a.getUrl("systemInformationGetData")).get({cache:"no-cache"}).then(async t=>{const o=await t.resolve();o.success===!0?e.innerHTML=o.html:s.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{a.handleAjaxError(t,e)})}}var c=new i;export{c as default};
