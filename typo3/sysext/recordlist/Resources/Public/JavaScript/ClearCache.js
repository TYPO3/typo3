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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/Icons"],function(e,n,a,c,r){"use strict";var s;return function(e){e.clearCache=".t3js-clear-page-cache",e.icon=".t3js-icon"}(s||(s={})),new(function(){function e(){var e=this;a(function(){e.registerClickHandler()})}return e.prototype.registerClickHandler=function(){var e=this;a(s.clearCache+":not([disabled])").on("click",function(n){n.preventDefault();var c=a(n.currentTarget),t=parseInt(c.data("id"),10),i=c.find(s.icon);c.prop("disabled",!0).addClass("disabled"),r.getIcon("spinner-circle-dark",r.sizes.small,null,"disabled").done(function(e){i.replaceWith(e)}),e.sendClearCacheRequest(t).always(function(){r.getIcon("actions-system-cache-clear",r.sizes.small).done(function(e){c.find(s.icon).replaceWith(e)}),c.prop("disabled",!1).removeClass("disabled")})})},e.prototype.sendClearCacheRequest=function(e){return a.ajax({url:TYPO3.settings.ajaxUrls.web_list_clearpagecache,data:{id:e},cache:!1,dataType:"json",success:function(e){!0===e.success?c.success(e.title,e.message,1):c.error(e.title,e.message,1)},error:function(){c.error("Clearing page caches went wrong on the server side.")}})},e}())});