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
define(["require","exports","jquery","../Icons","../Notification","../Viewport"],(function(e,t,c,r,o,n){"use strict";var i;return function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",e.menuItemSelector="a.toolbar-cache-flush-action",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon"}(i||(i={})),new(function(){function e(){var e=this;this.initializeEvents=function(){c(i.containerSelector).on("click",i.menuItemSelector,(function(t){t.preventDefault();var r=c(t.currentTarget).attr("href");r&&e.clearCache(r)}))},n.Topbar.Toolbar.registerEvent(this.initializeEvents)}return e.prototype.clearCache=function(e){c(i.containerSelector).removeClass("open");var t=c(i.toolbarIconSelector,i.containerSelector),n=t.clone();r.getIcon("spinner-circle-light",r.sizes.small).done((function(e){t.replaceWith(e)})),c.ajax({url:e,type:"post",cache:!1,success:function(e){!0===e.success?o.success(e.title,e.message):!1===e.success&&o.error(e.title,e.message)},error:function(){o.error("An error occurred","An error occurred while clearing the cache. It is likely not all caches were cleared as expected.")},complete:function(){c(i.toolbarIconSelector,i.containerSelector).replaceWith(n)}})},e}())}));