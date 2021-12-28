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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest","../Icons","../Notification","../Viewport"],(function(e,t,r,o,c,a,n){"use strict";var l;r=__importDefault(r),function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(l||(l={}));return new class{constructor(){this.initializeEvents=()=>{(0,r.default)(l.containerSelector).on("click",l.menuItemSelector,e=>{e.preventDefault();const t=(0,r.default)(e.currentTarget).attr("href");t&&this.clearCache(t)})},n.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){(0,r.default)(l.containerSelector).removeClass("open");const t=(0,r.default)(l.toolbarIconSelector,l.containerSelector),n=t.clone();c.getIcon("spinner-circle-light",c.sizes.small).then(e=>{t.replaceWith(e)}),new o(e).post({}).then(async e=>{const t=await e.resolve();!0===t.success?a.success(t.title,t.message):!1===t.success&&a.error(t.title,t.message)},()=>{a.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])}).finally(()=>{(0,r.default)(l.toolbarIconSelector,l.containerSelector).replaceWith(n)})}}}));