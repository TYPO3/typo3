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
import"bootstrap";import{AbstractInteractableModule as c}from"@typo3/install/module/abstract-interactable-module.js";import l from"@typo3/backend/modal.js";import m from"@typo3/backend/notification.js";import f from"@typo3/core/ajax/ajax-request.js";import{InfoBox as h}from"@typo3/install/renderable/info-box.js";import i from"@typo3/install/router.js";import p from"@typo3/core/event/regular-event.js";var o;(function(n){n.executeTrigger=".t3js-environmentCheck-execute",n.outputContainer=".t3js-environmentCheck-output"})(o||(o={}));class d extends c{initialize(e){super.initialize(e),this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js").then(()=>{this.runTests()}),new p("click",s=>{s.preventDefault(),this.runTests()}).delegateTo(e,o.executeTrigger)}runTests(){this.setModalButtonsState(!1);const e=this.getModalBody(),s=e.querySelector(o.outputContainer);s!==null&&this.renderProgressBar(s),new f(i.getUrl("environmentCheckGetStatus")).get({cache:"no-cache"}).then(async r=>{const t=await r.resolve();if(e.innerHTML=t.html,l.setButtons(t.buttons),t.success===!0&&typeof t.status=="object")for(const u of Object.values(t.status))for(const a of u)e.querySelector(o.outputContainer).append(h.create(a.severity,a.title,a.message));else m.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},r=>{i.handleAjaxError(r,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var g=new d;export{g as default};
