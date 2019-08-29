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
define(["require","exports","jquery","nprogress","TYPO3/CMS/Backend/Notification","datatables","TYPO3/CMS/Backend/jquery.clearable"],function(e,a,t,n,r){"use strict";var s;return function(e){e.extensionTable="#terTable",e.terUpdateAction=".update-from-ter",e.pagination=".pagination-wrap",e.splashscreen=".splash-receivedata",e.terTableDataTableWrapper="#terTableWrapper .dataTables_wrapper"}(s||(s={})),function(){function e(){}return e.prototype.initializeEvents=function(){var e=this;t(s.terUpdateAction).each(function(a,n){var r=t(n),s=r.attr("action");r.attr("action","#"),r.submit(function(){return e.updateFromTer(s,!0),!1}),e.updateFromTer(s,!1)})},e.prototype.updateFromTer=function(e,a){a&&(e+="&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1"),t(s.terUpdateAction).addClass("is-hidden"),t(s.extensionTable).hide(),t(s.splashscreen).addClass("is-shown"),t(s.terTableDataTableWrapper).addClass("is-loading"),t(s.pagination).addClass("is-loading");var i=!1;t.ajax({url:e,dataType:"json",cache:!1,beforeSend:function(){n.start()},success:function(e){e.errorMessage.length&&r.error(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],e.errorMessage,10);var a=t(s.terUpdateAction+" .time-since-last-update");a.text(e.timeSinceLastUpdate),a.attr("title",TYPO3.lang["extensionList.updateFromTer.lastUpdate.timeOfLastUpdate"]+e.lastUpdateTime),e.updated&&(i=!0,window.location.replace(window.location.href))},error:function(e,a,t){var n=a+"("+t+"): "+e.responseText;r.warning(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],n,10)},complete:function(){n.done(),i||(t(s.splashscreen).removeClass("is-shown"),t(s.terTableDataTableWrapper).removeClass("is-loading"),t(s.pagination).removeClass("is-loading"),t(s.terUpdateAction).removeClass("is-hidden"),t(s.extensionTable).show())}})},e}()});