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
import r from"@typo3/core/event/regular-event.js";import l from"@typo3/core/ajax/ajax-request.js";import{FileListActionEvent as n}from"@typo3/filelist/file-list-actions.js";import c from"@typo3/backend/info-window.js";class d{constructor(){new r(n.primary,e=>{e.preventDefault();const o=e.detail;o.action=n.select,document.dispatchEvent(new CustomEvent(n.select,{detail:o}))}).bindTo(document),new r(n.select,e=>{e.preventDefault();const t=e.detail.resources[0];t.type==="folder"&&this.loadContent(t)}).bindTo(document),new r(n.show,e=>{e.preventDefault();const t=e.detail.resources[0];c.showItem("_"+t.type.toUpperCase(),t.identifier)}).bindTo(document)}loadContent(e){if(e.type!=="folder")return;const o=document.location.href+"&contentOnly=1&expandFolder="+e.identifier;new l(o).get().then(t=>t.resolve()).then(t=>{const i=document.querySelector(".element-browser-main-content .element-browser-body");i.innerHTML=t})}}var s=new d;export{s as default};
