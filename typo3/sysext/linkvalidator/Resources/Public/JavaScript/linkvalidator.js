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
import a from"@typo3/core/document-service.js";import s from"@typo3/backend/notification.js";import"@typo3/backend/element/progress-bar-element.js";import n from"@typo3/core/event/regular-event.js";import l from"@typo3/backend/sortable-table.js";var e;(function(t){t.actionButtonSelectorCheck=".t3js-linkvalidator-action-button-check",t.actionButtonSelectorReport=".t3js-linkvalidator-action-button-report",t.reportTable=".t3js-linkvalidator-report-table"})(e||(e={}));var o;(function(t){t.brokenLinksTableIdReport="typo3-broken-links-table"})(o||(o={}));class c{constructor(){this.progressBar=null,a.ready().then(()=>{const r=document.getElementById(o.brokenLinksTableIdReport);r!==null&&r instanceof HTMLTableElement&&new l(r)}),this.initializeEvents()}getProgress(){return(!this.progressBar||!this.progressBar.isConnected)&&(this.progressBar=document.createElement("typo3-backend-progress-bar"),document.querySelector(e.reportTable).prepend(this.progressBar)),this.progressBar}initializeEvents(){new n("click",(r,i)=>{s.success(i.dataset.notificationMessage||"Event triggered","",2)}).delegateTo(document,e.actionButtonSelectorCheck),new n("click",()=>{this.getProgress().start()}).delegateTo(document,e.actionButtonSelectorReport)}}var p=new c;export{p as default};
