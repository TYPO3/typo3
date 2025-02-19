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
import{AbstractInteractableModule as u}from"@typo3/install/module/abstract-interactable-module.js";import h from"@typo3/backend/modal.js";import p from"@typo3/backend/notification.js";import f from"@typo3/core/ajax/ajax-request.js";import{InfoBox as i}from"@typo3/install/renderable/info-box.js";import c from"@typo3/install/renderable/severity.js";import l from"@typo3/install/router.js";import d from"@typo3/core/event/regular-event.js";var t;(function(n){n.checkTrigger=".t3js-tcaExtTablesCheck-check",n.outputContainer=".t3js-tcaExtTablesCheck-output"})(t||(t={}));class m extends u{initialize(a){super.initialize(a),this.loadModuleFrameAgnostic("@typo3/install/renderable/info-box.js").then(()=>{this.check()}),new d("click",e=>{e.preventDefault(),this.check()}).delegateTo(a,t.checkTrigger)}check(){this.setModalButtonsState(!1);const a=document.querySelector(t.outputContainer);a!==null&&this.renderProgressBar(a,{},"append");const e=this.getModalBody();new f(l.getUrl("tcaExtTablesCheck")).get({cache:"no-cache"}).then(async s=>{const o=await s.resolve();e.innerHTML=o.html,h.setButtons(o.buttons),o.success===!0&&Array.isArray(o.status)?o.status.length>0?(e.querySelector(t.outputContainer).append(i.create(c.warning,"Following extensions change TCA in ext_tables.php","Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS['TCA'] modifications")),o.status.forEach(r=>{e.querySelector(t.outputContainer).append(i.create(r.severity,r.title,r.message))})):e.querySelector(t.outputContainer).append(i.create(c.ok,"No TCA changes in ext_tables.php files. Good job!")):p.error("Something went wrong",'Please use the module "Check for broken extensions" to find a possible extension causing this issue.')},s=>{l.handleAjaxError(s,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var g=new m;export{g as default};
