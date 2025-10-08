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
import{AbstractInteractableModule as v}from"@typo3/install/module/abstract-interactable-module.js";import I from"@typo3/backend/modal.js";import k from"@typo3/backend/notification.js";import d from"@typo3/core/ajax/ajax-request.js";import c from"@typo3/install/router.js";import u from"@typo3/core/event/regular-event.js";import"@typo3/backend/element/icon-element.js";var o;(function(r){r.checkButton=".t3js-referenceIndex-check",r.updateButton=".t3js-referenceIndex-update",r.resultContainer=".t3js-referenceIndex-result"})(o||(o={}));class w extends v{initialize(t){super.initialize(t),this.loadContent(),new u("click",e=>{e.preventDefault(),this.check()}).delegateTo(t,o.checkButton),new u("click",e=>{e.preventDefault(),this.update()}).delegateTo(t,o.updateButton)}loadContent(){const t=this.getModalBody();new d(c.getUrl("referenceIndex")).get({cache:"no-cache"}).then(async e=>{const n=await e.resolve();n.success===!0&&n.html!==void 0&&(t.innerHTML=n.html,n.buttons!==void 0&&I.setButtons(n.buttons))},e=>{c.handleAjaxError(e,t)})}check(){this.processReferenceIndex(!0)}update(){this.processReferenceIndex(!1)}processReferenceIndex(t){this.setModalButtonsState(!1);const e=this.getModalBody(),n=e.querySelector(o.resultContainer),f=this.renderProgressBar(n,{label:t?"Checking reference index...":"Updating reference index..."});new d(c.getUrl()).post({install:{action:"referenceIndexUpdate",token:this.getModuleContent().dataset.referenceIndexToken,checkOnly:t?"1":"0"}}).then(async l=>{const s=await l.resolve();if(f.remove(),s.success===!0&&s.result&&n){const a=s.result.errors&&s.result.errors.length>0,h=a?"warning":"success",p=a?"actions-exclamation":"actions-check",m=s.result.resultText||(a?"Issues Found":"Reference index is up to date");let i="";a?i=`<ul class="list-unstyled">${s.result.errors.map(g=>`<li>${g}</li>`).join("")}</ul>`:i="Index integrity was perfect!";const x=`
              <div class="callout callout-${h}">
                <div class="callout-icon">
                  <span class="icon-emphasized">
                    <typo3-backend-icon identifier="${p}" size="small"></typo3-backend-icon>
                  </span>
                </div>
                <div class="callout-content">
                  <div class="callout-title">${m}</div>
                  <div class="callout-body">${i}</div>
                </div>
              </div>
            `;n.innerHTML=x}else k.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},l=>{c.handleAjaxError(l,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var y=new w;export{y as default};
