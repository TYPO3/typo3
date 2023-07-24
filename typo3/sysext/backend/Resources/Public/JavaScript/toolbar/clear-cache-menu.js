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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Icons from"@typo3/backend/icons.js";import Notification from"@typo3/backend/notification.js";import Viewport from"@typo3/backend/viewport.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector=".t3js-toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(Identifiers||(Identifiers={}));class ClearCacheMenu{constructor(){this.initializeEvents=()=>{const e=document.querySelector(Identifiers.containerSelector);new RegularEvent("click",((e,t)=>{e.preventDefault(),t.href&&this.clearCache(t.href)})).delegateTo(e,Identifiers.menuItemSelector)},Viewport.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){const t=document.querySelector(Identifiers.containerSelector);t.classList.remove("open");const o=t.querySelector(Identifiers.toolbarIconSelector),r=o.cloneNode(!0);Icons.getIcon("spinner-circle",Icons.sizes.small).then((e=>{o.replaceWith(document.createRange().createContextualFragment(e))})),new AjaxRequest(e).post({}).then((async e=>{const t=await e.resolve();!0===t.success?Notification.success(t.title,t.message):!1===t.success&&Notification.error(t.title,t.message)}),(()=>{Notification.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])})).finally((()=>{t.querySelector(Identifiers.toolbarIconSelector).replaceWith(r)}))}}export default new ClearCacheMenu;