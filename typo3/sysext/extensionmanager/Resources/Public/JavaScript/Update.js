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
define(["require","exports","jquery","nprogress","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,a,t,n,s,r){"use strict";var i;!function(e){e.extensionTable="#terTable",e.terUpdateAction=".update-from-ter",e.pagination=".pagination-wrap",e.splashscreen=".splash-receivedata",e.terTableWrapper="#terTableWrapper .table"}(i||(i={}));return class{initializeEvents(){t(i.terUpdateAction).each((e,a)=>{const n=t(a),s=n.attr("action");n.attr("action","#"),n.submit(()=>(this.updateFromTer(s,!0),!1)),this.updateFromTer(s,!1)})}updateFromTer(e,a){a&&(e+="&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1"),t(i.terUpdateAction).addClass("extensionmanager-is-hidden"),t(i.extensionTable).hide(),t(i.splashscreen).addClass("extensionmanager-is-shown"),t(i.terTableWrapper).addClass("extensionmanager-is-loading"),t(i.pagination).addClass("extensionmanager-is-loading");let o=!1;n.start(),new r(e).get().then(async e=>{const a=await e.resolve();a.errorMessage.length&&s.error(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],a.errorMessage,10);const n=t(i.terUpdateAction+" .extension-list-last-updated");n.text(a.timeSinceLastUpdate),n.attr("title",TYPO3.lang["extensionList.updateFromTer.lastUpdate.timeOfLastUpdate"]+a.lastUpdateTime),a.updated&&(o=!0,window.location.replace(window.location.href))},async e=>{const a=e.response.statusText+"("+e.response.status+"): "+await e.response.text();s.warning(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],a,10)}).finally(()=>{n.done(),o||(t(i.splashscreen).removeClass("extensionmanager-is-shown"),t(i.terTableWrapper).removeClass("extensionmanager-is-loading"),t(i.pagination).removeClass("extensionmanager-is-loading"),t(i.terUpdateAction).removeClass("extensionmanager-is-hidden"),t(i.extensionTable).show())})}}}));