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
import RegularEvent from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionAction}from"@typo3/backend/multi-record-selection-action.js";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Severity from"@typo3/backend/severity.js";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";import Notification from"@typo3/backend/notification.js";class MultiRecordSelectionDeleteAction{constructor(){new RegularEvent("multiRecordSelection:action:delete",this.delete).bindTo(document)}delete(e){e.preventDefault();const t=e.detail,o=MultiRecordSelectionAction.getEntityIdentifiers(t);if(!o.length)return;const n=t.configuration,r=n.tableName||"";if(""===r)return;const i=n.returnUrl||"";Modal.advanced({title:n.title||"Delete",content:n.content||"Are you sure you want to delete those records?",severity:SeverityEnum.warning,buttons:[{text:n.cancel||TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel",trigger:(e,t)=>t.hideModal()},{text:n.ok||TYPO3.lang["button.delete"]||"OK",btnClass:"btn-"+Severity.getCssClass(SeverityEnum.warning),name:"delete",trigger:(e,t)=>{t.hideModal(),AjaxDataHandler.process("cmd["+r+"]["+o.join(",")+"][delete]=1").then((e=>{if(e.hasErrors)throw e.messages;""!==i?window.location.href=i:t.ownerDocument.location.reload()})).catch((()=>{Notification.error("Could not delete records")}))}}]})}}export default new MultiRecordSelectionDeleteAction;