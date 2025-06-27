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
import m from"@typo3/backend/link-browser.js";import c from"@typo3/core/event/regular-event.js";import{FileListActionEvent as r}from"@typo3/filelist/file-list-actions.js";import d from"@typo3/core/ajax/ajax-request.js";import p from"@typo3/backend/info-window.js";import u from"@typo3/backend/notification.js";class w{constructor(){new c(r.primary,e=>{e.preventDefault();const n=e.detail;n.action=r.select,document.dispatchEvent(new CustomEvent(r.select,{detail:n}))}).bindTo(document),new c(r.select,e=>{e.preventDefault();const t=e.detail.resources[0];t.type==="file"&&this.insertLink(t),t.type==="folder"&&this.loadContent(t)}).bindTo(document),new c(r.show,e=>{e.preventDefault();const t=e.detail.resources[0];p.showItem("_"+t.type.toUpperCase(),t.identifier)}).bindTo(document)}insertLink(e){new d(TYPO3.settings.ajaxUrls.link_resource).post({identifier:e.identifier}).then(async t=>{const o=await t.resolve();o.status.forEach(i=>{u.showMessage(i.title,i.message,i.severity)}),o.success&&m.finalizeFunction(o.link)})}async loadContent(e){if(e.type!=="folder")return;const n=document.location.href+"&contentOnly=1&expandFolder="+e.identifier,o=await(await new d(n).get()).resolve(),i=document.querySelector(".element-browser-main-content .element-browser-body");i.innerHTML=o;const s=document.querySelector("typo3-backend-component-filestorage-browser-tree");if(s){const l=encodeURIComponent(e.identifier),a=s.nodes.find(f=>f.identifier===l);a&&(await s.expandNodeParents(a),s.selectNode(a,!1))}}}var y=new w;export{y as default};
