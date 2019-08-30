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
define(["require","exports","jquery","../Icons","../Notification","../Viewport"],function(e,t,r,c,o,n){"use strict";var a;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(a||(a={}));return new class{constructor(){this.initializeEvents=(()=>{r(a.containerSelector).on("click",a.menuItemSelector,e=>{e.preventDefault();const t=r(e.currentTarget).attr("href");t&&this.clearCache(t)})}),n.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(e){r(a.containerSelector).removeClass("open");const t=r(a.toolbarIconSelector,a.containerSelector),n=t.clone();c.getIcon("spinner-circle-light",c.sizes.small).done(e=>{t.replaceWith(e)}),r.ajax({url:e,type:"post",cache:!1,complete:(e,t)=>{r(a.toolbarIconSelector,a.containerSelector).replaceWith(n),"success"===t&&""===e.responseText||o.error("An error occurred","An error occurred while clearing the cache. It is likely not all caches were cleared as expected.")}})}}});