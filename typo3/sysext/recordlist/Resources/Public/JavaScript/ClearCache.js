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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/Icons"],function(e,s,a,r,c){"use strict";var t;!function(e){e.clearCache=".t3js-clear-page-cache",e.icon=".t3js-icon"}(t||(t={}));return new class{constructor(){a(()=>{this.registerClickHandler()})}registerClickHandler(){a(t.clearCache+":not([disabled])").on("click",e=>{e.preventDefault();const s=a(e.currentTarget),r=parseInt(s.data("id"),10),n=s.find(t.icon);s.prop("disabled",!0).addClass("disabled"),c.getIcon("spinner-circle-dark",c.sizes.small,null,"disabled").done(e=>{n.replaceWith(e)}),this.sendClearCacheRequest(r).always(()=>{c.getIcon("actions-system-cache-clear",c.sizes.small).done(e=>{s.find(t.icon).replaceWith(e)}),s.prop("disabled",!1).removeClass("disabled")})})}sendClearCacheRequest(e){return a.ajax({url:TYPO3.settings.ajaxUrls.web_list_clearpagecache,data:{id:e},cache:!1,dataType:"json",success:e=>{!0===e.success?r.success(e.title,e.message,1):r.error(e.title,e.message,1)},error:()=>{r.error("Clearing page caches went wrong on the server side.")}})}}});