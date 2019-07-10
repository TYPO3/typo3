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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification"],function(e,r,c,t){"use strict";return new function(){c(function(){c(".t3js-clear-page-cache").on("click",function(e){e.preventDefault();var r=c(e.currentTarget).data("id");c.ajax({url:TYPO3.settings.ajaxUrls.web_list_clearpagecache+"&id="+r,cache:!1,dataType:"json",success:function(e){!0===e.success?t.success(e.title,e.message,1):t.error(e.title,e.message,1)},error:function(){t.error("Clearing page caches went wrong on the server side.")}})})})}});