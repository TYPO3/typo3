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
import p from"@typo3/core/ajax/ajax-request.js";import f from"@typo3/backend/notification.js";import b from"@typo3/core/event/regular-event.js";import{sudoModeInterceptor as y}from"@typo3/backend/security/sudo-mode-interceptor.js";import r from"~labels/backend.layout";import{HiddenContentCountChangedEvent as h}from"@typo3/backend/layout-module/page-layout-event.js";import{HiddenContentCountChangedEvent as L}from"@typo3/backend/layout-module/page-layout-event.js";import"@typo3/backend/element/icon-element.js";new b("click",async(s,d)=>{s.preventDefault(),s.stopPropagation();const i=d;if(i.disabled)return;i.disabled=!0;const e=i.closest(".t3js-page-ce");if(e===null){i.disabled=!1;return}const c=e.dataset.table,l=parseInt(e.dataset.uid??"0",10),g=e.classList.contains("t3js-hidden-record"),u=i.querySelector("typo3-backend-icon");try{const t=await(await new p(TYPO3.settings.ajaxUrls.record_toggle_visibility).addMiddleware(y).post({table:c,uid:l,action:g?"show":"hide"})).resolve();if(e.classList.toggle("t3-page-ce-hidden",!t.isVisible),e.classList.toggle("t3js-hidden-record",!t.isVisible),t.isVisible)e.style.display="";else{const a=document.querySelector("typo3-backend-page-layout-toggle-hidden");a&&!a.active&&(e.style.display="none")}u?.setAttribute("identifier",t.isVisible?"actions-edit-hide":"actions-edit-unhide"),i.title=t.isVisible?r.get("hide"):r.get("unHide");const o=e.querySelector(".t3-page-ce-header-left [data-contextmenu-trigger] .t3js-icon");o&&t.icon&&o.replaceWith(document.createRange().createContextualFragment(t.icon));const m=document.querySelectorAll(".t3js-hidden-record").length;document.dispatchEvent(new h(m))}catch(n){if(n&&typeof n.resolve=="function"){const t=await n.resolve();for(const o of t.messages??[])f.error(o.title,o.message)}}finally{i.disabled=!1}}).delegateTo(document,'button[data-action="content-element-visibility-toggle"]');export{L as HiddenContentCountChangedEvent};
