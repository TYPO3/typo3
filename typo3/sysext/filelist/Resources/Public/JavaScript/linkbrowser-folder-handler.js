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
import a from"@typo3/backend/link-browser.js";import o from"@typo3/core/event/regular-event.js";import{FileListActionEvent as n}from"@typo3/filelist/file-list-actions.js";import l from"@typo3/backend/info-window.js";import c from"@typo3/core/ajax/ajax-request.js";import d from"@typo3/backend/notification.js";class u{constructor(){new o("click",(e,t)=>{e.preventDefault(),a.finalizeFunction(t.dataset.linkbrowserLink)}).delegateTo(document,"[data-linkbrowser-link]"),new o(n.primary,e=>{e.preventDefault();const t=e.detail;t.action=n.select,document.dispatchEvent(new CustomEvent(n.select,{detail:t}))}).bindTo(document),new o(n.select,e=>{e.preventDefault();const i=e.detail.resources[0];i.type==="folder"&&this.insertLink(i)}).bindTo(document),new o(n.show,e=>{e.preventDefault();const i=e.detail.resources[0];l.showItem("_"+i.type.toUpperCase(),i.identifier)}).bindTo(document)}insertLink(e){new c(TYPO3.settings.ajaxUrls.link_resource).post({identifier:e.identifier}).then(async i=>{const r=await i.resolve();r.status.forEach(s=>{d.showMessage(s.title,s.message,s.severity)}),r.success&&a.finalizeFunction(r.link)})}}var f=new u;export{f as default};
