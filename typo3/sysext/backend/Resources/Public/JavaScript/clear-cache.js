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
import l from"@typo3/backend/notification.js";import i from"@typo3/backend/icons.js";import u from"@typo3/core/event/regular-event.js";import d from"@typo3/core/ajax/ajax-request.js";var c;(function(o){o.clearCache=".t3js-clear-page-cache",o.icon=".t3js-icon"})(c||(c={}));class a{constructor(){this.registerClickHandler()}static setDisabled(t,e){t.disabled=e,t.classList.toggle("disabled",e)}static sendClearCacheRequest(t){const e=new d(TYPO3.settings.ajaxUrls.web_list_clearpagecache).withQueryArguments({id:t}).get({cache:"no-cache"});return e.then(async s=>{const r=await s.resolve();r.success===!0?l.success(r.title,r.message,1):l.error(r.title,r.message,1)},()=>{l.error("Clearing page caches went wrong on the server side.")}),e}registerClickHandler(){const t=document.querySelector(`${c.clearCache}:not([disabled])`);t!==null&&new u("click",e=>{e.preventDefault();const s=e.currentTarget,r=parseInt(s.dataset.id,10);a.setDisabled(s,!0),i.getIcon("spinner-circle",i.sizes.small,null,"disabled").then(n=>{s.querySelector(c.icon).outerHTML=n}),a.sendClearCacheRequest(r).finally(()=>{i.getIcon("actions-system-cache-clear",i.sizes.small).then(n=>{s.querySelector(c.icon).outerHTML=n}),a.setDisabled(s,!1)})}).bindTo(t)}}var g=new a;export{g as default};
