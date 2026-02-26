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
import l from"@typo3/backend/notification.js";import o from"@typo3/backend/icons.js";import u from"@typo3/core/event/regular-event.js";import d from"@typo3/core/ajax/ajax-request.js";import i from"~labels/core.cache";var a;(function(g){g.clearCache=".t3js-clear-page-cache",g.icon=".t3js-icon"})(a||(a={}));class c{constructor(){this.registerClickHandler()}static setDisabled(t,e){t.disabled=e,t.classList.toggle("disabled",e)}static sendClearCacheRequest(t){const e=new d(TYPO3.settings.ajaxUrls.clearcache_page).post({id:t});return e.then(async s=>{const r=await s.resolve();r?.success===!1?l.error(r.title??i.get("notification.error.title"),r.message??i.get("notification.error.message")):l.success(r?.title??i.get("notification.success.title"),r?.message??i.get("notification.success.message"))},()=>{l.error(i.get("notification.error.title"),i.get("notification.error.message"))}),e}registerClickHandler(){const t=document.querySelector(`${a.clearCache}:not([disabled])`);t!==null&&new u("click",e=>{e.preventDefault();const s=e.currentTarget,r=parseInt(s.dataset.id,10);c.setDisabled(s,!0),o.getIcon("spinner-circle",o.sizes.small,null,"disabled").then(n=>{s.querySelector(a.icon).outerHTML=n}),c.sendClearCacheRequest(r).finally(()=>{o.getIcon("actions-system-cache-clear",o.sizes.small).then(n=>{s.querySelector(a.icon).outerHTML=n}),c.setDisabled(s,!1)})}).bindTo(t)}}var f=new c;export{f as default};
