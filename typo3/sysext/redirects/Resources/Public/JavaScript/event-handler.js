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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import NotificationService from"@typo3/backend/notification.js";import DeferredAction from"@typo3/backend/action-button/deferred-action.js";class EventHandler{constructor(){document.addEventListener("typo3:redirects:slugChanged",e=>this.onSlugChanged(e.detail))}dispatchCustomEvent(e,t=null){const r=new CustomEvent(e,{detail:t});document.dispatchEvent(r)}onSlugChanged(e){let t=[];const r=e.correlations;e.autoUpdateSlugs&&t.push({label:TYPO3.lang["notification.redirects.button.revert_update"],action:new DeferredAction(()=>this.revert([r.correlationIdSlugUpdate,r.correlationIdRedirectCreation]))}),e.autoCreateRedirects&&t.push({label:TYPO3.lang["notification.redirects.button.revert_redirect"],action:new DeferredAction(()=>this.revert([r.correlationIdRedirectCreation]))});let i=TYPO3.lang["notification.slug_only.title"],o=TYPO3.lang["notification.slug_only.message"];e.autoCreateRedirects&&(i=TYPO3.lang["notification.slug_and_redirects.title"],o=TYPO3.lang["notification.slug_and_redirects.message"]),NotificationService.info(i,o,0,t)}revert(e){const t=new AjaxRequest(TYPO3.settings.ajaxUrls.redirects_revert_correlation).withQueryArguments({correlation_ids:e}).get();return t.then(async e=>{const t=await e.resolve();"ok"===t.status&&NotificationService.success(t.title,t.message),"error"===t.status&&NotificationService.error(t.title,t.message)}).catch(()=>{NotificationService.error(TYPO3.lang.redirects_error_title,TYPO3.lang.redirects_error_message)}),t}}export default new EventHandler;