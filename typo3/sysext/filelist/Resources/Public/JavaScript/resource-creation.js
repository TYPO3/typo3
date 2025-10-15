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
import s from"@typo3/core/event/regular-event.js";import f from"@typo3/core/ajax/ajax-request.js";import{FileListActionEvent as n}from"@typo3/filelist/file-list-actions.js";import m from"@typo3/backend/info-window.js";class p{constructor(){new s(n.primary,e=>{e.preventDefault();const t=e.detail;t.action=n.select,document.dispatchEvent(new CustomEvent(n.select,{detail:t}))}).bindTo(document),new s(n.select,e=>{e.preventDefault();const o=e.detail.resources[0];o.type==="folder"&&this.loadContent(o)}).bindTo(document),new s(n.show,e=>{e.preventDefault();const o=e.detail.resources[0];m.showItem("_"+o.type.toUpperCase(),o.identifier)}).bindTo(document)}async loadContent(e){if(e.type!=="folder")return;const t=document.location.href+"&contentOnly=1&expandFolder="+e.identifier,c=await(await new f(t).get()).resolve(),d=document.querySelector(".element-browser-main-content .element-browser-body");d.innerHTML=c;const r=document.querySelector("typo3-backend-component-filestorage-browser-tree");if(r){const a=encodeURIComponent(e.identifier),i=r.nodes.find(l=>l.identifier===a);i&&(await r.expandNodeParents(i),r.selectNode(i,!1))}}}var u=new p;export{u as default};
