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
define(["require","exports","jquery","nprogress","TYPO3/CMS/Backend/Notification","datatables"],(function(e,a,t,n,s){"use strict";var r;!function(e){e.extensionTable="#terTable",e.terUpdateAction=".update-from-ter",e.pagination=".pagination-wrap",e.splashscreen=".splash-receivedata",e.terTableDataTableWrapper="#terTableWrapper .dataTables_wrapper"}(r||(r={}));return class{initializeEvents(){t(r.terUpdateAction).each((e,a)=>{const n=t(a),s=n.attr("action");n.attr("action","#"),n.submit(()=>(this.updateFromTer(s,!0),!1)),this.updateFromTer(s,!1)})}updateFromTer(e,a){a&&(e+="&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1"),t(r.terUpdateAction).addClass("extensionmanager-is-hidden"),t(r.extensionTable).hide(),t(r.splashscreen).addClass("extensionmanager-is-shown"),t(r.terTableDataTableWrapper).addClass("extensionmanager-is-loading"),t(r.pagination).addClass("extensionmanager-is-loading");let i=!1;t.ajax({url:e,dataType:"json",cache:!1,beforeSend:()=>{n.start()},success:e=>{e.errorMessage.length&&s.error(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],e.errorMessage,10);const a=t(r.terUpdateAction+" .extension-list-last-updated");a.text(e.timeSinceLastUpdate),a.attr("title",TYPO3.lang["extensionList.updateFromTer.lastUpdate.timeOfLastUpdate"]+e.lastUpdateTime),e.updated&&(i=!0,window.location.replace(window.location.href))},error:(e,a,t)=>{const n=a+"("+t+"): "+e.responseText;s.warning(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],n,10)},complete:()=>{n.done(),i||(t(r.splashscreen).removeClass("extensionmanager-is-shown"),t(r.terTableDataTableWrapper).removeClass("extensionmanager-is-loading"),t(r.pagination).removeClass("extensionmanager-is-loading"),t(r.terUpdateAction).removeClass("extensionmanager-is-hidden"),t(r.extensionTable).show())}})}}}));