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
var __awaiter=this&&this.__awaiter||function(e,t,o,r){return new(o||(o=Promise))((function(n,c){function i(e){try{s(r.next(e))}catch(e){c(e)}}function a(e){try{s(r.throw(e))}catch(e){c(e)}}function s(e){var t;e.done?n(e.value):(t=e.value,t instanceof o?t:new o((function(e){e(t)}))).then(i,a)}s((r=r.apply(e,t||[])).next())}))};define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest","../Icons","../Notification","../Viewport"],(function(e,t,o,r,n,c,i){"use strict";var a;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(a||(a={}));return new class{constructor(){this.initializeEvents=()=>{o(a.containerSelector).on("click",a.menuItemSelector,e=>{e.preventDefault();const t=o(e.currentTarget).attr("href");t&&this.clearCache(t)})},i.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){o(a.containerSelector).removeClass("open");const t=o(a.toolbarIconSelector,a.containerSelector),i=t.clone();n.getIcon("spinner-circle-light",n.sizes.small).done(e=>{t.replaceWith(e)}),new r(e).post({}).then(e=>__awaiter(this,void 0,void 0,(function*(){const t=yield e.resolve();!0===t.success?c.success(t.title,t.message):!1===t.success&&c.error(t.title,t.message)})),()=>{c.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])}).finally(()=>{o(a.toolbarIconSelector,a.containerSelector).replaceWith(i)})}}}));