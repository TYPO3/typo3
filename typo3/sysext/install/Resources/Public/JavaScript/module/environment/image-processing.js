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
import"bootstrap";import{AbstractInteractableModule as T}from"@typo3/install/module/abstract-interactable-module.js";import w from"@typo3/backend/modal.js";import y from"@typo3/backend/notification.js";import h from"@typo3/core/ajax/ajax-request.js";import{InfoBox as f}from"@typo3/install/renderable/info-box.js";import x from"@typo3/install/renderable/severity.js";import m from"@typo3/install/router.js";import A from"@typo3/core/event/regular-event.js";var o;(function(n){n.executeTrigger=".t3js-imageProcessing-execute",n.testContainer=".t3js-imageProcessing-twinContainer",n.twinImageTemplate="#t3js-imageProcessing-twinImage-template",n.commandContainer=".t3js-imageProcessing-command",n.commandText=".t3js-imageProcessing-command-text",n.twinImages=".t3js-imageProcessing-images"})(o||(o={}));class P extends T{initialize(t){super.initialize(t),this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js").then(()=>{this.getData()}),new A("click",a=>{a.preventDefault(),this.runTests()}).delegateTo(t,o.executeTrigger)}getData(){const t=this.getModalBody();new h(m.getUrl("imageProcessingGetData")).get({cache:"no-cache"}).then(async a=>{const r=await a.resolve();r.success===!0?(t.innerHTML=r.html,w.setButtons(r.buttons),this.runTests()):y.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},a=>{m.handleAjaxError(a,t)})}runTests(){const t=this.getModalBody();this.setModalButtonsState(!1);const a=this.findInModal(o.twinImageTemplate),r=[];t.querySelectorAll(o.testContainer).forEach(c=>{c.replaceChildren(f.create(x.loading,"Loading..."));const p=new h(m.getUrl(c.dataset.test)).get({cache:"no-cache"}).then(async l=>{const e=await l.resolve();if(e.success===!0){c.innerHTML="",Array.isArray(e.status)&&e.status.forEach(s=>{c.append(f.create(s.severity,s.title,s.message))});const i=a.content.cloneNode(!0);if(e.fileExists===!0&&(i.querySelector("img.reference")?.setAttribute("src",e.referenceFile),i.querySelector("img.result")?.setAttribute("src",e.outputFile),i.querySelectorAll(o.twinImages).forEach(s=>s.hidden=!1)),Array.isArray(e.command)&&e.command.length>0){const s=i.querySelector(o.commandContainer);s!==null&&(s.hidden=!1);const g=[];e.command.forEach(u=>{g.push(`<strong>Command:</strong>
`+u[1]),u.length===3&&g.push(`<strong>Result:</strong>
`+u[2])});const d=i.querySelector(o.commandText);d!==null&&(d.innerHTML=g.join(`
`))}c.append(i)}},l=>{m.handleAjaxError(l,t)});r.push(p)}),Promise.all(r).then(()=>{this.setModalButtonsState(!0)})}}var j=new P;export{j as default};
