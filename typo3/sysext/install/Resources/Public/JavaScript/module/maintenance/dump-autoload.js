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
import t from"@typo3/backend/notification.js";import r from"@typo3/core/ajax/ajax-request.js";import n from"@typo3/install/router.js";import{AbstractInlineModule as u}from"@typo3/install/module/abstract-inline-module.js";class i extends u{initialize(s){this.setButtonState(s,!1),new r(n.getUrl("dumpAutoload")).get({cache:"no-cache"}).then(async o=>{const e=await o.resolve();e.success===!0&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(a=>{t.success(a.message)}):t.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{t.error("Autoloader not dumped","Dumping autoload files failed for unknown reasons. Check the system for broken extensions and try again.")}).finally(()=>{this.setButtonState(s,!0)})}}var l=new i;export{l as default};
