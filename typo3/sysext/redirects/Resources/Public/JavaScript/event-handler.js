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
import s from"@typo3/core/ajax/ajax-request.js";import o from"@typo3/backend/notification.js";import i from"@typo3/backend/action-button/deferred-action.js";class c{constructor(){document.addEventListener("typo3:redirects:slugChanged",e=>this.onSlugChanged(e.detail))}dispatchCustomEvent(e,r=null){const n=new CustomEvent(e,{detail:r});document.dispatchEvent(n)}onSlugChanged(e){const r=[],n=e.correlations;e.autoUpdateSlugs&&r.push({label:TYPO3.lang["notification.redirects.button.revert_update"],action:new i(async()=>{await this.revert([n.correlationIdPageUpdate,n.correlationIdSlugUpdate,n.correlationIdRedirectCreation])})}),e.autoCreateRedirects&&r.push({label:TYPO3.lang["notification.redirects.button.revert_redirect"],action:new i(async()=>{await this.revert([n.correlationIdRedirectCreation])})});let t=TYPO3.lang["notification.slug_only.title"],a=TYPO3.lang["notification.slug_only.message"];e.autoCreateRedirects&&(t=TYPO3.lang["notification.slug_and_redirects.title"],a=TYPO3.lang["notification.slug_and_redirects.message"]),o.info(t,a,0,r)}revert(e){const r=new s(TYPO3.settings.ajaxUrls.redirects_revert_correlation).withQueryArguments({correlation_ids:e}).get();return r.then(async n=>{const t=await n.resolve();t.status==="ok"&&(o.success(t.title,t.message),window.location.reload(),top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))),t.status==="error"&&o.error(t.title,t.message)}).catch(()=>{o.error(TYPO3.lang.redirects_error_title,TYPO3.lang.redirects_error_message)}),r}}var l=new c;export{l as default};
