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
define(["require","exports","jquery","../Icons","../Notification","../Viewport"],(function(e,c,r,t,o,n){"use strict";var a;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(a||(a={}));return new class{constructor(){this.initializeEvents=()=>{r(a.containerSelector).on("click",a.menuItemSelector,e=>{e.preventDefault();const c=r(e.currentTarget).attr("href");c&&this.clearCache(c)})},n.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){r(a.containerSelector).removeClass("open");const c=r(a.toolbarIconSelector,a.containerSelector),n=c.clone();t.getIcon("spinner-circle-light",t.sizes.small).done(e=>{c.replaceWith(e)}),r.ajax({url:e,type:"post",cache:!1,success:e=>{!0===e.success?o.success(e.title,e.message):!1===e.success&&o.error(e.title,e.message)},error:()=>{o.error("An error occurred","An error occurred while clearing the cache. It is likely not all caches were cleared as expected.")},complete:()=>{r(a.toolbarIconSelector,a.containerSelector).replaceWith(n)}})}}}));