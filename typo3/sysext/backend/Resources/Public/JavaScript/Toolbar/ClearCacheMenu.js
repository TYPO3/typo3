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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest","../Icons","../Notification","../Viewport"],(function(e,t,r,o,c,n,a){"use strict";var s;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(s||(s={}));return new class{constructor(){this.initializeEvents=()=>{r(s.containerSelector).on("click",s.menuItemSelector,e=>{e.preventDefault();const t=r(e.currentTarget).attr("href");t&&this.clearCache(t)})},a.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){r(s.containerSelector).removeClass("open");const t=r(s.toolbarIconSelector,s.containerSelector),a=t.clone();c.getIcon("spinner-circle-light",c.sizes.small).then(e=>{t.replaceWith(e)}),new o(e).post({}).then(async e=>{const t=await e.resolve();!0===t.success?n.success(t.title,t.message):!1===t.success&&n.error(t.title,t.message)},()=>{n.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])}).finally(()=>{r(s.toolbarIconSelector,s.containerSelector).replaceWith(a)})}}}));