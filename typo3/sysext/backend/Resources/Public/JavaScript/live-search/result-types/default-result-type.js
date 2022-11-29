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
import LiveSearch from"@typo3/backend/toolbar/live-search.js";import"@typo3/backend/live-search/element/provider/page-provider-result-item.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";export function registerType(e){LiveSearch.addInvokeHandler(e,"switch_backend_user",(e=>{new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:e.extraData.uid}).then((async e=>{const t=await e.resolve();!0===t.success&&t.url?top.window.location.href=t.url:Notification.error("Switching to user went wrong.")}))}))}