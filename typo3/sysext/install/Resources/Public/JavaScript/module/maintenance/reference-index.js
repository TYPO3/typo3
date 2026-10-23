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
import{AbstractInteractableModule as I}from"@typo3/install/module/abstract-interactable-module.js";import k from"@typo3/backend/modal.js";import y from"@typo3/backend/notification.js";import u from"@typo3/core/ajax/ajax-request.js";import a from"@typo3/install/router.js";import f from"@typo3/core/event/regular-event.js";import"@typo3/backend/element/icon-element.js";var s;(function(o){o.checkButton=".t3js-referenceIndex-check",o.updateButton=".t3js-referenceIndex-update",o.resultContainer=".t3js-referenceIndex-result"})(s||(s={}));class w extends I{initialize(t){super.initialize(t),this.loadContent(),new f("click",e=>{e.preventDefault(),this.check()}).delegateTo(t,s.checkButton),new f("click",e=>{e.preventDefault(),this.update()}).delegateTo(t,s.updateButton)}loadContent(){const t=this.getModalBody();new u(a.getUrl("referenceIndex")).get({cache:"no-cache"}).then(async e=>{const n=await e.resolve();n.success===!0&&n.html!==void 0&&(t.innerHTML=n.html,n.buttons!==void 0&&k.setButtons(n.buttons))},e=>{a.handleAjaxError(e,t)})}check(){this.processReferenceIndex(!0)}update(){this.processReferenceIndex(!1)}processReferenceIndex(t){this.setModalButtonsState(!1);const e=this.getModalBody(),n=e.querySelector(s.resultContainer),h=this.renderProgressBar(n,{label:t?"Checking reference index...":"Updating reference index..."});new u(a.getUrl()).post({install:{action:"referenceIndexUpdate",token:this.getModuleContent().dataset.referenceIndexToken,checkOnly:t?"1":"0"}}).then(async i=>{const r=await i.resolve();if(h.remove(),r.success===!0&&r.result&&n){const d=r.result.errors??[],c=d.length>0,p=c?"warning":"success",m=c?"actions-exclamation":"actions-check",x=r.result.resultText||(c?"Issues Found":"Reference index is up to date");let l="";c?l=`<ul class="list-unstyled">${d.map(v=>`<li>${v}</li>`).join("")}</ul>`:l="Index integrity is perfect.";const g=`
              <div class="callout callout-${p}">
                <div class="callout-icon">
                  <span class="icon-emphasized">
                    <typo3-backend-icon identifier="${m}" size="small"></typo3-backend-icon>
                  </span>
                </div>
                <div class="callout-content">
                  <div class="callout-title">${x}</div>
                  <div class="callout-body">${l}</div>
                </div>
              </div>
            `;n.innerHTML=g}else y.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},i=>{a.handleAjaxError(i,e)}).finally(()=>{this.setModalButtonsState(!0)})}}var B=new w;export{B as default};
