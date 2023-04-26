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
import Modal from"@typo3/backend/modal.js";import{html}from"lit";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";import{lll}from"@typo3/core/lit-helper.js";class SchedulerAddGroups{constructor(){this.selector=".t3js-create-group",this.initialize()}initialize(){const e=document.querySelector(this.selector);e&&e.addEventListener("click",(e=>{e.preventDefault();const t=html`
          <form name="scheduler-create-group" @submit=${this.createGroup}>
            <label class="form-label" for="actionCreateGroup">Group name</label>
            <input class="form-control" id="actionCreateGroup" required="" name="action[createGroup]" autofocus type="text">
          </form>
        `;Modal.advanced({content:t,title:lll("scheduler.createGroup")||"New group",size:Modal.sizes.small,buttons:[{trigger:()=>Modal.dismiss(),text:lll("scheduler.modalCancel")||"Cancel",btnClass:"btn-default",name:"cancel"},{trigger:()=>{Modal.currentModal.querySelector('form[name="scheduler-create-group"]').requestSubmit()},text:lll("scheduler.modalOk")||"Create group",btnClass:"btn-primary",name:"ok"}]}).addEventListener("typo3-modal-shown",(()=>{Modal.currentModal.querySelector('input[name="action[createGroup]"]').focus()}))}))}createGroup(e){e.preventDefault();const t=new FormData(e.target).get("action[createGroup]").toString(),r="NEW"+Math.random().toString(36).slice(2,7),o="&data[tx_scheduler_task_group]["+r+"][pid]=0&data[tx_scheduler_task_group]["+r+"][groupName]="+encodeURIComponent(t);return new AjaxRequest(TYPO3.settings.ajaxUrls.record_process).post(o,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then((async e=>await e.resolve())).then((e=>(e.hasErrors&&Notification.error(lll("scheduler.group.error.title"),lll("scheduler.group.error.message")+' "'+t+'"!'),e.messages.forEach((e=>{Notification.info(e.title,e.message)})),e))).catch((()=>{Notification.error(lll("scheduler.group.error.title"),lll("scheduler.group.error.message")+' "'+t+'"!')})).finally((()=>{if(document.querySelector("#task_group")){const e=document.forms[0];e.querySelector('[name="tx_scheduler[select_latest_group]"]').value="1",e.submit()}else window.location.reload();Modal.dismiss()}))}}export default new SchedulerAddGroups;