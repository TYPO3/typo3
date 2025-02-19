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
import a from"@typo3/backend/link-browser.js";import s from"@typo3/core/event/regular-event.js";import{FileListActionEvent as i}from"@typo3/filelist/file-list-actions.js";import l from"@typo3/core/ajax/ajax-request.js";import c from"@typo3/backend/info-window.js";import d from"@typo3/backend/notification.js";class f{constructor(){new s(i.primary,e=>{e.preventDefault();const n=e.detail;n.action=i.select,document.dispatchEvent(new CustomEvent(i.select,{detail:n}))}).bindTo(document),new s(i.select,e=>{e.preventDefault();const t=e.detail.resources[0];t.type==="file"&&this.insertLink(t),t.type==="folder"&&this.loadContent(t)}).bindTo(document),new s(i.show,e=>{e.preventDefault();const t=e.detail.resources[0];c.showItem("_"+t.type.toUpperCase(),t.identifier)}).bindTo(document)}insertLink(e){new l(TYPO3.settings.ajaxUrls.link_resource).post({identifier:e.identifier}).then(async t=>{const o=await t.resolve();o.status.forEach(r=>{d.showMessage(r.title,r.message,r.severity)}),o.success&&a.finalizeFunction(o.link)})}loadContent(e){if(e.type!=="folder")return;const n=document.location.href+"&contentOnly=1&expandFolder="+e.identifier;new l(n).get().then(t=>t.resolve()).then(t=>{const o=document.querySelector(".element-browser-main-content .element-browser-body");o.innerHTML=t})}}var u=new f;export{u as default};
