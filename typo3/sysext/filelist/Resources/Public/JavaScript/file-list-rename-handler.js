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
import RegularEvent from"@typo3/core/event/regular-event.js";import{html}from"lit";import{FileListActionEvent}from"@typo3/filelist/file-list-actions.js";import{default as Modal}from"@typo3/backend/modal.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";import Viewport from"@typo3/backend/viewport.js";class FileListRenameHandler{constructor(){new RegularEvent(FileListActionEvent.rename,(e=>{const t=e.detail,r=t.resources[0],n=Modal.advanced({title:TYPO3.lang["file_rename.title"]||"Rename",type:Modal.types.default,size:Modal.sizes.small,content:this.composeEditForm(r),buttons:[{text:TYPO3.lang["file_rename.button.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel",trigger:()=>{n.hideModal()}},{text:TYPO3.lang["file_rename.button.rename"]||"Rename",btnClass:"btn-primary",name:"rename",trigger:()=>{const e=n.querySelector("form");e?.requestSubmit()}}],callback:function(e){const r=e.querySelector("form");r.addEventListener("submit",(r=>{r.preventDefault();const n=new FormData(r.target),o=Object.fromEntries(n).name.toString();if(t.resources[0].name!==o){new AjaxRequest(TYPO3.settings.ajaxUrls.resource_rename).post({identifier:t.resources[0].identifier,resourceName:o}).then((async t=>{const r=await t.resolve();if(r.status.length>0&&r.status.forEach((e=>{r.success?Notification.success(e.title,e.message):Notification.error(e.title,e.message)})),"folder"===r.resource?.type){const e=Viewport.ContentContainer.getUrl();new URL(e,window.location.origin).searchParams.get("id")===r.origin.identifier?Viewport.ContentContainer.setUrl(e+"&id="+r.resource.identifier):Viewport.ContentContainer.refresh()}else Viewport.ContentContainer.refresh();top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh")),e.hideModal()}))}})),e.addEventListener("typo3-modal-shown",(()=>{r.querySelector("input")?.focus()}))}})})).bindTo(document)}composeEditForm(e){return html`
      <form>
        <label class="form-label" for="rename_target">
          ${TYPO3.lang["file_rename.label"]??"New filename"}
        </label>
        <input id="rename_target" name="name" class="form-control" value="${e.name}" required>
      </form>
    `}}export default new FileListRenameHandler;