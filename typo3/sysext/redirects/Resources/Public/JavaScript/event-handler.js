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
import c from"@typo3/core/ajax/ajax-request.js";import n from"@typo3/backend/notification.js";import a from"@typo3/backend/action-button/deferred-action.js";import i from"~labels/redirects.slug_service";class l{constructor(){document.addEventListener("typo3:redirects:slugChanged",e=>this.onSlugChanged(e.detail))}dispatchCustomEvent(e,r=null){const o=new CustomEvent(e,{detail:r});document.dispatchEvent(o)}onSlugChanged(e){const r=[],o=e.correlations;e.autoUpdateSlugs&&r.push({label:i.get("notification.redirects.button.revert_update"),action:new a(async()=>{await this.revert([o.correlationIdPageUpdate,o.correlationIdSlugUpdate,o.correlationIdRedirectCreation])})}),e.autoCreateRedirects&&r.push({label:i.get("notification.redirects.button.revert_redirect"),action:new a(async()=>{await this.revert([o.correlationIdRedirectCreation])})});let t=i.get("notification.slug_only.title"),s=i.get("notification.slug_only.message");e.autoCreateRedirects&&(t=i.get("notification.slug_and_redirects.title"),s=i.get("notification.slug_and_redirects.message")),n.info(t,s,0,r)}revert(e){const r=new c(TYPO3.settings.ajaxUrls.redirects_revert_correlation).post({correlation_ids:e});return r.then(async o=>{const t=await o.resolve();t.status==="ok"&&(n.success(t.title,t.message),window.location.reload(),top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))),t.status==="error"&&n.error(t.title,t.message)}).catch(()=>{n.error(i.get("redirects_error_title"),i.get("redirects_error_message"))}),r}}var d=new l;export{d as default};
