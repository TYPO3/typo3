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
import t from"@typo3/backend/notification.js";import o from"@typo3/core/ajax/ajax-request.js";import n from"@typo3/install/router.js";import{AbstractInlineModule as i}from"@typo3/install/module/abstract-inline-module.js";class c extends i{initialize(r){this.setButtonState(r,!1),new o(n.getUrl("cacheClearAll","maintenance")).get({cache:"no-cache"}).then(async s=>{const e=await s.resolve();e.success===!0&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(a=>{t.success(a.title,a.message)}):t.error("Something went wrong clearing caches")},()=>{t.error("Clearing caches failed","Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again. Also ensure you are properly authenticated and the server does not report specific PHP parse errors or JavaScript errors are listed in the browser console.")}).finally(()=>{this.setButtonState(r,!0)})}}var l=new c;export{l as default};
