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
import m from"@typo3/core/ajax/ajax-request.js";import f from"@typo3/backend/notification.js";import h from"@typo3/core/event/regular-event.js";import{sudoModeInterceptor as b}from"@typo3/backend/security/sudo-mode-interceptor.js";import{topDocument as y}from"@typo3/core/utility/top-window.js";import r from"~labels/backend.layout";import{HiddenContentCountChangedEvent as v}from"@typo3/backend/layout-module/page-layout-event.js";import{HiddenContentCountChangedEvent as S}from"@typo3/backend/layout-module/page-layout-event.js";import"@typo3/backend/element/icon-element.js";new h("click",async(s,d)=>{s.preventDefault(),s.stopPropagation();const o=d;if(o.disabled)return;o.disabled=!0;const t=o.closest(".t3js-page-ce");if(t===null){o.disabled=!1;return}const c=t.dataset.table,l=parseInt(t.dataset.uid??"0",10),g=t.classList.contains("t3js-hidden-record"),u=o.querySelector("typo3-backend-icon");try{const e=await(await new m(TYPO3.settings.ajaxUrls.record_toggle_visibility).addMiddleware(b).post({table:c,uid:l,action:g?"show":"hide"})).resolve();if(t.classList.toggle("t3-page-ce-hidden",!e.isVisible),t.classList.toggle("t3js-hidden-record",!e.isVisible),e.isVisible)t.style.display="";else{const a=document.querySelector("typo3-backend-page-layout-toggle-hidden");a&&!a.active&&(t.style.display="none")}u?.setAttribute("identifier",e.isVisible?"actions-edit-hide":"actions-edit-unhide"),o.title=e.isVisible?r.get("hide"):r.get("unHide");const i=t.querySelector(".t3-page-ce-header-left [data-contextmenu-trigger] .t3js-icon");i&&e.icon&&i.replaceWith(document.createRange().createContextualFragment(e.icon));const p=document.querySelectorAll(".t3js-hidden-record").length;document.dispatchEvent(new v(p)),e.pageTreeNeedsRefresh&&y.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))}catch(n){if(n&&typeof n.resolve=="function"){const e=await n.resolve();for(const i of e.messages??[])f.error(i.title,i.message)}}finally{o.disabled=!1}}).delegateTo(document,'button[data-action="content-element-visibility-toggle"]');export{S as HiddenContentCountChangedEvent};
