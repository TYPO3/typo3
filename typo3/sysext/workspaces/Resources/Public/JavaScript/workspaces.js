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
import{AjaxResponse as i}from"@typo3/core/ajax/ajax-response.js";import c from"@typo3/core/ajax/ajax-request.js";import{SeverityEnum as l}from"@typo3/backend/enum/severity.js";import"@typo3/backend/element/progress-bar-element.js";import n from"@typo3/backend/notification.js";import p from"@typo3/backend/modal.js";import{html as m}from"lit";import s from"~labels/workspaces.messages";class d{constructor(){this.ajaxRoute="workspace_dispatch",this.progressBar=null}renderSendToStageWindow(r){const e=r[0].result,o=p.advanced({title:s.get("actionSendToStage"),content:m`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`,severity:l.info,buttons:[{text:s.get("cancel"),active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{o.hideModal()}},{text:s.get("ok"),btnClass:"btn-primary",name:"ok"}],callback:t=>{const a=t.ownerDocument.createElement("typo3-workspaces-send-to-stage-form");a.data=e,t.querySelector(".t3js-modal-body").replaceChildren(a)}});return o}sendRemoteRequest(r,e="#workspace-content-wrapper"){this.progressBar=document.createElement("typo3-backend-progress-bar"),document.querySelector(e).prepend(this.progressBar),this.progressBar.start();const o={"Content-Type":"application/json; charset=utf-8"};return new c(TYPO3.settings.ajaxUrls[this.ajaxRoute]).post(r,{headers:o}).catch(async t=>{throw await this.notifyRequestFailure(t),t}).finally(()=>{this.progressBar&&this.progressBar.done()})}generateRemotePayloadBody(r,e){return e instanceof Array||(e=[e]),{data:e,method:r}}async notifyRequestFailure(r){const e=s.get("error.dispatch.title");if(!(r instanceof i)){n.error(e,s.get("error.dispatch.unavailable"));return}let o;try{o=await r.resolve("json")}catch{n.error(e,s.get("error.dispatch.unavailable"));return}const t=[];if(Array.isArray(o))for(const a of o)a?.error?.message&&t.push(a.error.message);if(t.length===0){n.error(e,s.get("error.dispatch.unknown"));return}t.forEach(a=>n.error(e,a))}}export{d as default};
