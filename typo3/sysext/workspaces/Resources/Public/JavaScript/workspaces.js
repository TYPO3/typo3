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
import s from"@typo3/core/ajax/ajax-request.js";import{SeverityEnum as i}from"@typo3/backend/enum/severity.js";import a from"nprogress";import l from"@typo3/backend/modal.js";import{html as c}from"lit";class d{constructor(){this.tid=0}renderSendToStageWindow(e){const t=e[0].result,o=l.advanced({title:TYPO3.lang.actionSendToStage,content:c`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`,severity:i.info,buttons:[{text:TYPO3.lang.cancel,active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{o.hideModal()}},{text:TYPO3.lang.ok,btnClass:"btn-primary",name:"ok"}],callback:r=>{const n=r.ownerDocument.createElement("typo3-workspaces-send-to-stage-form");n.data=t,n.TYPO3lang=TYPO3.lang,r.querySelector(".t3js-modal-body").replaceChildren(n)}});return o}sendRemoteRequest(e,t="#workspace-content-wrapper"){return a.configure({parent:t,showSpinner:!1}),a.start(),new s(TYPO3.settings.ajaxUrls.workspace_dispatch).post(e,{headers:{"Content-Type":"application/json; charset=utf-8"}}).finally(()=>a.done())}generateRemotePayload(e,t={}){return this.generateRemotePayloadBody("RemoteServer",e,t)}generateRemoteMassActionsPayload(e,t={}){return this.generateRemotePayloadBody("MassActions",e,t)}generateRemoteActionsPayload(e,t={}){return this.generateRemotePayloadBody("Actions",e,t)}generateRemotePayloadBody(e,t,o){return o instanceof Array?o.push(TYPO3.settings.Workspaces.token):o=[o,TYPO3.settings.Workspaces.token],{action:e,data:o,method:t,type:"rpc",tid:this.tid++}}}export{d as default};
