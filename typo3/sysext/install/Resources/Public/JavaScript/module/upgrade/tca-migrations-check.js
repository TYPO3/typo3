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
import{AbstractInteractableModule as u}from"@typo3/install/module/abstract-interactable-module.js";import p from"@typo3/backend/modal.js";import h from"@typo3/core/ajax/ajax-request.js";import{FlashMessage as d}from"@typo3/install/renderable/flash-message.js";import{InfoBox as i}from"@typo3/install/renderable/info-box.js";import c from"@typo3/install/renderable/severity.js";import l from"@typo3/install/router.js";import g from"@typo3/core/event/regular-event.js";var t;(function(a){a.checkTrigger=".t3js-tcaMigrationsCheck-check",a.outputContainer=".t3js-tcaMigrationsCheck-output"})(t||(t={}));class m extends u{initialize(r){super.initialize(r),Promise.all([this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js"),this.loadModuleFrameAgnostic("@typo3/install/renderable/flash-message.js")]).then(()=>{this.check()}),new g("click",e=>{e.preventDefault(),this.check()}).delegateTo(r,t.checkTrigger)}check(){this.setModalButtonsState(!1);const r=document.querySelector(t.outputContainer);r!==null&&this.renderProgressBar(r,{},"append");const e=this.getModalBody();new h(l.getUrl("tcaMigrationsCheck")).get({cache:"no-cache"}).then(async n=>{const o=await n.resolve();e.innerHTML=o.html,p.setButtons(o.buttons),o.success===!0&&Array.isArray(o.status)?o.status.length>0?(e.querySelector(t.outputContainer).append(i.create(c.warning,"TCA migrations need to be applied","Check the following list and apply needed changes.")),o.status.forEach(s=>{e.querySelector(t.outputContainer).append(i.create(s.severity,s.title,s.message))})):e.querySelector(t.outputContainer).append(i.create(c.ok,"No TCA migrations need to be applied","Your TCA looks good.")):e.querySelector(t.outputContainer).append(d.create(c.error,"Something went wrong",'Use "Check for broken extensions"'))},n=>{l.handleAjaxError(n,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var f=new m;export{f as default};
