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
import u from"@typo3/core/ajax/ajax-request.js";import l from"@typo3/backend/icons.js";import s from"@typo3/backend/notification.js";import f from"@typo3/backend/viewport.js";import g from"@typo3/core/event/regular-event.js";import t from"~labels/core.cache";var e;(function(a){a.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",a.menuItemSelector=".t3js-toolbar-cache-flush-action",a.toolbarIconSelector=".toolbar-item-icon .t3js-icon"})(e||(e={}));class p{constructor(){this.initializeEvents=()=>{const n=document.querySelector(e.containerSelector);new g("click",(o,r)=>{o.preventDefault(),r.dataset.endpoint&&this.clearCache(r.dataset.endpoint)}).delegateTo(n,e.menuItemSelector)},f.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(n){const o=document.querySelector(e.containerSelector);o.classList.remove("open");const r=o.querySelector(e.toolbarIconSelector),m=r.cloneNode(!0);l.getIcon("spinner-circle",l.sizes.small).then(i=>{r.replaceWith(document.createRange().createContextualFragment(i))}),new u(n).post({}).then(async i=>{const c=await i.resolve();c?.success===!1?s.error(c.title??t.get("notification.error.title"),c.message??t.get("notification.error.message")):s.success(c?.title??t.get("notification.success.title"),c?.message??t.get("notification.success.message"))},()=>{s.error(t.get("notification.error.title"),t.get("notification.error.message"))}).finally(()=>{o.querySelector(e.toolbarIconSelector).replaceWith(m)})}}var b=new p;export{b as default};
