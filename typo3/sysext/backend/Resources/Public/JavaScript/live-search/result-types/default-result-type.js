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
import LiveSearchConfigurator from"@typo3/backend/live-search/live-search-configurator.js";import"@typo3/backend/live-search/element/provider/page-provider-result-item.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";export function registerType(e){LiveSearchConfigurator.addInvokeHandler(e,"switch_backend_user",(e=>{new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:e.extraData.uid}).then((async e=>{const r=await e.resolve();!0===r.success&&r.url?top.window.location.href=r.url:Notification.error("Switching to user went wrong.")}))}))}