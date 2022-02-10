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
import Notification from"@typo3/backend/notification.js";import Icons from"@typo3/backend/icons.js";import RegularEvent from"@typo3/core/event/regular-event.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";var Identifiers;!function(e){e.clearCache=".t3js-clear-page-cache",e.icon=".t3js-icon"}(Identifiers||(Identifiers={}));class ClearCache{static setDisabled(e,t){e.disabled=t,e.classList.toggle("disabled",t)}static sendClearCacheRequest(e){const t=new AjaxRequest(TYPO3.settings.ajaxUrls.web_list_clearpagecache).withQueryArguments({id:e}).get({cache:"no-cache"});return t.then(async e=>{const t=await e.resolve();!0===t.success?Notification.success(t.title,t.message,1):Notification.error(t.title,t.message,1)},()=>{Notification.error("Clearing page caches went wrong on the server side.")}),t}constructor(){this.registerClickHandler()}registerClickHandler(){const e=document.querySelector(Identifiers.clearCache+":not([disabled])");null!==e&&new RegularEvent("click",e=>{e.preventDefault();const t=e.currentTarget,a=parseInt(t.dataset.id,10);ClearCache.setDisabled(t,!0),Icons.getIcon("spinner-circle-dark",Icons.sizes.small,null,"disabled").then(e=>{t.querySelector(Identifiers.icon).outerHTML=e}),ClearCache.sendClearCacheRequest(a).finally(()=>{Icons.getIcon("actions-system-cache-clear",Icons.sizes.small).then(e=>{t.querySelector(Identifiers.icon).outerHTML=e}),ClearCache.setDisabled(t,!1)})}).bindTo(e)}}export default new ClearCache;