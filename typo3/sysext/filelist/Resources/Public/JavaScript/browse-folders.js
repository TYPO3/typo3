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
import l from"@typo3/backend/element-browser.js";import r from"@typo3/core/event/regular-event.js";import{FileListActionSelector as a,FileListActionUtility as d,FileListActionEvent as o}from"@typo3/filelist/file-list-actions.js";import u from"@typo3/backend/info-window.js";class c{constructor(){this.importSelection=e=>{e.preventDefault();const t=e.detail.checkboxes;if(!t.length)return;const i=[];t.forEach(n=>{if(n.checked){const m=n.closest(a.elementSelector),s=d.getResourceForElement(m);s.type==="folder"&&s.identifier&&i.unshift(s)}}),i.length&&(i.forEach(function(n){c.insertElement(n.identifier)}),l.focusOpenerAndClose())},new r(o.primary,e=>{e.preventDefault();const t=e.detail;t.originalAction=o.primary,t.action=o.select,document.dispatchEvent(new CustomEvent(o.select,{detail:t}))}).bindTo(document),new r(o.select,e=>{e.preventDefault();const t=e.detail,i=t.resources[0];i.type==="folder"&&c.insertElement(i.identifier,t.originalAction===o.primary)}).bindTo(document),new r(o.show,e=>{e.preventDefault();const i=e.detail.resources[0];u.showItem("_"+i.type.toUpperCase(),i.identifier)}).bindTo(document),new r("multiRecordSelection:action:import",this.importSelection).bindTo(document)}static insertElement(e,t){return l.insertElement("",e,e,e,t)}}var f=new c;export{f as default};
