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
define(["require","exports","jquery","nprogress","TYPO3/CMS/Backend/Notification","datatables"],(function(e,a,t,s,r){"use strict";var n;!function(e){e.extensionTable="#terTable",e.terUpdateAction=".update-from-ter",e.pagination=".pagination-wrap",e.splashscreen=".splash-receivedata",e.terTableDataTableWrapper="#terTableWrapper .dataTables_wrapper"}(n||(n={}));return class{initializeEvents(){t(n.terUpdateAction).each((e,a)=>{const s=t(a),r=s.attr("action");s.attr("action","#"),s.submit(()=>(this.updateFromTer(r,!0),!1)),this.updateFromTer(r,!1)})}updateFromTer(e,a){a&&(e+="&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1"),t(n.terUpdateAction).addClass("is-hidden"),t(n.extensionTable).hide(),t(n.splashscreen).addClass("is-shown"),t(n.terTableDataTableWrapper).addClass("is-loading"),t(n.pagination).addClass("is-loading");let i=!1;t.ajax({url:e,dataType:"json",cache:!1,beforeSend:()=>{s.start()},success:e=>{e.errorMessage.length&&r.error(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],e.errorMessage,10);const a=t(n.terUpdateAction+" .time-since-last-update");a.text(e.timeSinceLastUpdate),a.attr("title",TYPO3.lang["extensionList.updateFromTer.lastUpdate.timeOfLastUpdate"]+e.lastUpdateTime),e.updated&&(i=!0,window.location.replace(window.location.href))},error:(e,a,t)=>{const s=a+"("+t+"): "+e.responseText;r.warning(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],s,10)},complete:()=>{s.done(),i||(t(n.splashscreen).removeClass("is-shown"),t(n.terTableDataTableWrapper).removeClass("is-loading"),t(n.pagination).removeClass("is-loading"),t(n.terUpdateAction).removeClass("is-hidden"),t(n.extensionTable).show())}})}}}));