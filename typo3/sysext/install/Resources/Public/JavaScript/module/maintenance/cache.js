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
import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";import{AbstractInlineModule}from"@typo3/install/module/abstract-inline-module.js";class Cache extends AbstractInlineModule{initialize(e){this.setButtonState(e,!1),new AjaxRequest(Router.getUrl("cacheClearAll","maintenance")).get({cache:"no-cache"}).then(async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach(e=>{Notification.success(e.title,e.message)}):Notification.error("Something went wrong clearing caches")},()=>{Notification.error("Clearing caches failed","Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again.")}).finally(()=>{this.setButtonState(e,!0)})}}export default new Cache;