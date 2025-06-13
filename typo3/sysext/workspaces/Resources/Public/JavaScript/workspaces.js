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
import s from"@typo3/core/ajax/ajax-request.js";import{SeverityEnum as l}from"@typo3/backend/enum/severity.js";import o from"nprogress";import i from"@typo3/backend/modal.js";import{html as c}from"lit";class d{renderSendToStageWindow(t){const e=t[0].result,a=i.advanced({title:TYPO3.lang.actionSendToStage,content:c`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`,severity:l.info,buttons:[{text:TYPO3.lang.cancel,active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{a.hideModal()}},{text:TYPO3.lang.ok,btnClass:"btn-primary",name:"ok"}],callback:r=>{const n=r.ownerDocument.createElement("typo3-workspaces-send-to-stage-form");n.data=e,n.TYPO3lang=TYPO3.lang,r.querySelector(".t3js-modal-body").replaceChildren(n)}});return a}sendRemoteRequest(t,e="#workspace-content-wrapper"){return o.configure({parent:e,showSpinner:!1}),o.start(),new s(TYPO3.settings.ajaxUrls.workspace_dispatch).post(t,{headers:{"Content-Type":"application/json; charset=utf-8"}}).finally(()=>o.done())}generateRemotePayloadBody(t,e){return e instanceof Array||(e=[e]),{data:e,method:t}}}export{d as default};
