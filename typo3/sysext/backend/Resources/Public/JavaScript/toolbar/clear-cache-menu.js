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
import m from"@typo3/core/ajax/ajax-request.js";import s from"@typo3/backend/icons.js";import l from"@typo3/backend/notification.js";import u from"@typo3/backend/viewport.js";import h from"@typo3/core/event/regular-event.js";var e;(function(c){c.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",c.menuItemSelector=".t3js-toolbar-cache-flush-action",c.toolbarIconSelector=".toolbar-item-icon .t3js-icon"})(e||(e={}));class f{constructor(){this.initializeEvents=()=>{const a=document.querySelector(e.containerSelector);new h("click",(o,r)=>{o.preventDefault(),r.href&&this.clearCache(r.href)}).delegateTo(a,e.menuItemSelector)},u.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(a){const o=document.querySelector(e.containerSelector);o.classList.remove("open");const r=o.querySelector(e.toolbarIconSelector),i=r.cloneNode(!0);s.getIcon("spinner-circle",s.sizes.small).then(n=>{r.replaceWith(document.createRange().createContextualFragment(n))}),new m(a).post({}).then(async n=>{const t=await n.resolve();t.success===!0?l.success(t.title,t.message):t.success===!1&&l.error(t.title,t.message)},()=>{l.error(TYPO3.lang["flushCaches.error"],TYPO3.lang["flushCaches.error.description"])}).finally(()=>{o.querySelector(e.toolbarIconSelector).replaceWith(i)})}}var p=new f;export{p as default};
