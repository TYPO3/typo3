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
define(["require","exports","jquery","../Icons","../Notification","../Viewport"],(function(e,t,r,c,o,n){"use strict";var s;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(s||(s={}));return new class{constructor(){this.initializeEvents=()=>{r(s.containerSelector).on("click",s.menuItemSelector,e=>{e.preventDefault();const t=r(e.currentTarget).attr("href");t&&this.clearCache(t)})},n.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){r(s.containerSelector).removeClass("open");const t=r(s.toolbarIconSelector,s.containerSelector),n=t.clone();c.getIcon("spinner-circle-light",c.sizes.small).done(e=>{t.replaceWith(e)}),r.ajax({url:e,type:"post",cache:!1,success:e=>{!0===e.success?o.success(e.title,e.message):!1===e.success&&o.error(e.title,e.message)},error:()=>{o.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])},complete:()=>{r(s.toolbarIconSelector,s.containerSelector).replaceWith(n)}})}}}));