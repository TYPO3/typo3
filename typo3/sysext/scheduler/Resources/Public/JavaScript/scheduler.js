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
import r from"@typo3/backend/sortable-table.js";import a from"@typo3/core/event/regular-event.js";import c from"@typo3/backend/icons.js";import l from"@typo3/backend/storage/persistent.js";import{MultiRecordSelectionSelectors as d}from"@typo3/backend/multi-record-selection.js";import u from"@typo3/core/document-service.js";class i{constructor(){u.ready().then(()=>{this.initializeEvents()})}static storeCollapseState(o,s){let t={};l.isset("moduleData.scheduler_manage")&&(t=l.get("moduleData.scheduler_manage"));const e={};e[o]=s?1:0,t={...t,...e},l.set("moduleData.scheduler_manage",t)}initializeEvents(){document.querySelectorAll("[data-scheduler-table]").forEach(o=>{new r(o)}),new a("show.bs.collapse",this.toggleCollapseIcon.bind(this)).bindTo(document),new a("hide.bs.collapse",this.toggleCollapseIcon.bind(this)).bindTo(document),new a("multiRecordSelection:action:go",this.executeTasks.bind(this)).bindTo(document),new a("multiRecordSelection:action:go_cron",this.executeTasks.bind(this)).bindTo(document)}toggleCollapseIcon(o){const s=o.type==="hide.bs.collapse",t=document.querySelector('.t3js-toggle-table[data-bs-target="#'+o.target.id+'"] .t3js-icon');t!==null&&c.getIcon(s?"actions-view-list-expand":"actions-view-list-collapse",c.sizes.small).then(e=>{t.replaceWith(document.createRange().createContextualFragment(e))}),i.storeCollapseState(o.target.dataset.table,s)}executeTasks(o){const s=document.querySelector('[data-multi-record-selection-form="'+o.detail.identifier+'"]');if(s===null)return;const t=[];if(o.detail.checkboxes.forEach(e=>{const n=e.closest(d.elementSelector);n!==null&&n.dataset.taskId&&t.push(n.dataset.taskId)}),t.length){if(o.type==="multiRecordSelection:action:go_cron"){const e=document.createElement("input");e.setAttribute("type","hidden"),e.setAttribute("name","scheduleCron"),e.setAttribute("value",t.join(",")),s.append(e)}else{const e=document.createElement("input");e.setAttribute("type","hidden"),e.setAttribute("name","execute"),e.setAttribute("value",t.join(",")),s.append(e)}s.submit()}}}var m=new i;export{m as default};
