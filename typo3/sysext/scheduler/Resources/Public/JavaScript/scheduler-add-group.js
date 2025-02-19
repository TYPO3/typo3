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
import r from"@typo3/backend/modal.js";import{html as m}from"lit";import d from"@typo3/core/ajax/ajax-request.js";import l from"@typo3/backend/notification.js";import{lll as e}from"@typo3/core/lit-helper.js";class p{constructor(){this.selector=".t3js-create-group",this.initialize()}initialize(){const o=document.querySelector(this.selector);o&&o.addEventListener("click",u=>{u.preventDefault();const a=m`<form name=scheduler-create-group @submit=${this.createGroup}><label class=form-label for=actionCreateGroup>Group name</label> <input class=form-control id=actionCreateGroup required name=action[createGroup] autofocus type=text></form>`;r.advanced({content:a,title:e("scheduler.createGroup")||"New group",size:r.sizes.small,buttons:[{trigger:()=>r.dismiss(),text:e("scheduler.modalCancel")||"Cancel",btnClass:"btn-default",name:"cancel"},{trigger:()=>{r.currentModal.querySelector('form[name="scheduler-create-group"]').requestSubmit()},text:e("scheduler.modalOk")||"Create group",btnClass:"btn-primary",name:"ok"}]}).addEventListener("typo3-modal-shown",()=>{r.currentModal.querySelector('input[name="action[createGroup]"]').focus()})})}createGroup(o){o.preventDefault();const a=new FormData(o.target).get("action[createGroup]").toString(),n="NEW"+Math.random().toString(36).slice(2,7),c="&data[tx_scheduler_task_group]["+n+"][pid]=0&data[tx_scheduler_task_group]["+n+"][groupName]="+encodeURIComponent(a);return new d(TYPO3.settings.ajaxUrls.record_process).post(c,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then(async t=>await t.resolve()).then(t=>(t.hasErrors&&l.error(e("scheduler.group.error.title"),e("scheduler.group.error.message")+' "'+a+'"!'),t.messages.forEach(s=>{l.info(s.title,s.message)}),t)).catch(()=>{l.error(e("scheduler.group.error.title"),e("scheduler.group.error.message")+' "'+a+'"!')}).finally(()=>{if(document.querySelector("#task_group")){const s=document.forms[0],i=s.querySelector('[name="tx_scheduler[select_latest_group]"]');i.value="1",s.submit()}else window.location.reload();r.dismiss()})}}var f=new p;export{f as default};
