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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/ActionButton/DeferredAction"],function(e,t,r,i,n){"use strict";return new class{constructor(){document.addEventListener("typo3:redirects:slugChanged",e=>this.onSlugChanged(e.detail))}onSlugChanged(e){let t=[];const r=e.correlations;e.autoUpdateSlugs&&t.push({label:TYPO3.lang["notification.redirects.button.revert_update"],action:new n(()=>this.revert([r.correlationIdSlugUpdate,r.correlationIdRedirectCreation]))}),e.autoCreateRedirects&&t.push({label:TYPO3.lang["notification.redirects.button.revert_redirect"],action:new n(()=>this.revert([r.correlationIdRedirectCreation]))});let a=TYPO3.lang["notification.slug_only.title"],o=TYPO3.lang["notification.slug_only.message"];e.autoCreateRedirects&&(a=TYPO3.lang["notification.slug_and_redirects.title"],o=TYPO3.lang["notification.slug_and_redirects.message"]),i.info(a,o,0,t)}revert(e){r.ajax({url:TYPO3.settings.ajaxUrls.redirects_revert_correlation,data:{correlation_ids:e}}).done(e=>{"ok"===e.status&&i.success(e.title,e.message),"error"===e.status&&i.error(e.title,e.message)}).fail(()=>{i.error(TYPO3.lang.redirects_error_title,TYPO3.lang.redirects_error_message)})}}});