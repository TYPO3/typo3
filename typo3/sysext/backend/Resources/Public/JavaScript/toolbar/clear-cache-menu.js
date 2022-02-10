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
import $ from"jquery";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Icons from"@typo3/backend/icons.js";import Notification from"@typo3/backend/notification.js";import Viewport from"@typo3/backend/viewport.js";var Identifiers;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(Identifiers||(Identifiers={}));class ClearCacheMenu{constructor(){this.initializeEvents=()=>{$(Identifiers.containerSelector).on("click",Identifiers.menuItemSelector,e=>{e.preventDefault();const t=$(e.currentTarget).attr("href");t&&this.clearCache(t)})},Viewport.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){$(Identifiers.containerSelector).removeClass("open");const t=$(Identifiers.toolbarIconSelector,Identifiers.containerSelector),o=t.clone();Icons.getIcon("spinner-circle-light",Icons.sizes.small).then(e=>{t.replaceWith(e)}),new AjaxRequest(e).post({}).then(async e=>{const t=await e.resolve();!0===t.success?Notification.success(t.title,t.message):!1===t.success&&Notification.error(t.title,t.message)},()=>{Notification.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])}).finally(()=>{$(Identifiers.toolbarIconSelector,Identifiers.containerSelector).replaceWith(o)})}}export default new ClearCacheMenu;