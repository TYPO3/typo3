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
import s from"@typo3/core/ajax/ajax-request.js";import{SeverityEnum as l}from"@typo3/backend/enum/severity.js";import n from"nprogress";import i from"@typo3/backend/modal.js";import{html as d}from"lit";class c{renderSendToStageWindow(e){const t=e[0].result,o=i.advanced({title:TYPO3.lang.actionSendToStage,content:d`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`,severity:l.info,buttons:[{text:TYPO3.lang.cancel,active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{o.hideModal()}},{text:TYPO3.lang.ok,btnClass:"btn-primary",name:"ok"}],callback:r=>{const a=r.ownerDocument.createElement("typo3-workspaces-send-to-stage-form");a.data=t,a.TYPO3lang=TYPO3.lang,r.querySelector(".t3js-modal-body").replaceChildren(a)}});return o}sendRemoteRequest(e,t="#workspace-content-wrapper"){return n.configure({parent:t,showSpinner:!1}),n.start(),new s(TYPO3.settings.ajaxUrls.workspace_dispatch).post(e,{headers:{"Content-Type":"application/json; charset=utf-8"}}).finally(()=>n.done())}generateRemotePayload(e,t={}){return this.generateRemotePayloadBody("RemoteServer",e,t)}generateRemoteMassActionsPayload(e,t={}){return this.generateRemotePayloadBody("MassActions",e,t)}generateRemoteActionsPayload(e,t={}){return this.generateRemotePayloadBody("Actions",e,t)}generateRemotePayloadBody(e,t,o){return o instanceof Array||(o=[o]),{action:e,data:o,method:t}}}export{c as default};
