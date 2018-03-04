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
define(["require","exports","jquery","../Icons","../Notification","../Viewport"],function(e,t,r,c,o,n){"use strict";var i,a;return(a=i||(i={})).containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",a.menuItemSelector="a.toolbar-cache-flush-action",a.toolbarIconSelector=".toolbar-item-icon .t3js-icon",new(function(){function e(){var e=this;this.initializeEvents=function(){r(i.containerSelector).on("click",i.menuItemSelector,function(t){t.preventDefault();var c=r(t.currentTarget).attr("href");c&&e.clearCache(c)})},n.Topbar.Toolbar.registerEvent(this.initializeEvents)}return e.prototype.clearCache=function(e){r(i.containerSelector).removeClass("open");var t=r(i.toolbarIconSelector,i.containerSelector),n=t.clone();c.getIcon("spinner-circle-light",c.sizes.small).done(function(e){t.replaceWith(e)}),r.ajax({url:e,type:"post",cache:!1,complete:function(e,t){r(i.toolbarIconSelector,i.containerSelector).replaceWith(n),"success"===t&&""===e.responseText||o.error("An error occurred","An error occurred while clearing the cache. It is likely not all caches were cleared as expected.")}})},e}())});