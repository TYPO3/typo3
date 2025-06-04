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
import a from"@typo3/backend/live-search/live-search-configurator.js";import"@typo3/backend/live-search/element/provider/page-provider-result-item.js";import i from"@typo3/core/ajax/ajax-request.js";import n from"@typo3/backend/notification.js";import s from"@typo3/backend/window-manager.js";function u(t){a.addInvokeHandler(t,"switch_backend_user",o=>{new i(TYPO3.settings.ajaxUrls.switch_user).post({targetUser:o.extraData.uid}).then(async r=>{const e=await r.resolve();e.success===!0&&e.url?top.window.location.href=e.url:n.error("Switching to user went wrong.")})}),a.addInvokeHandler(t,"preview",(o,r)=>{s.localOpen(r.url,!0)})}export{u as registerType};
